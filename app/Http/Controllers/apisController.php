<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Api;
use Ixudra\Curl\Facades\Curl;
use Carbon\Carbon;

class ApisController extends Controller
{
    public function login()
    {
        return view( 'login.form' );
    }

    public function auth()
    {
        /**
         * An app which auth, should end all theses informations
         * must checks ip, host, envato licence
        **/

        if( request([ 'host', 'ip', 'app_name', 'app_version', 'gcp_proxy', 'request_uri', 'envato_licence' ]) ) {
            extract( request([ 'host', 'ip', 'app_name', 'app_version', 'gcp_proxy', 'request_uri', 'envato_licence' ]) );
            $query    =     Api::where( compact( 'host', 'ip', 'app_name', 'app_version', 'gcp_proxy', 'request_uri', 'envato_licence' ) );

            if( ! $this->envatoCheck( $envato_licence, $app_name ) ) {
                return redirect( 'error/403/incorrectLicence' );
            }

            if( $query->count() > 0 ) {
                $api        =   $query->first();
                session([ 'app_id' => $api->id ]);
                return redirect( '/api/google-request' );
            } else { // if app is not connected

                $api                        =   new Api;
                $api->app_name              =   $app_name;
                $api->host                  =   $ip;
                $api->ip                    =   $host;
                $api->app_version           =   $app_version;
                $api->gcp_proxy             =   $gcp_proxy;
                $api->request_uri           =   $request_uri;
                $api->envato_licence        =   $envato_licence;
                $api->save();

                session([ 'app_id' => $api->id ]);
                return redirect( '/api/google-request' );
            }
        }
    }

    /**
     * Returns app details
     * @return json
    **/

    public function details()
    {
        $api    =   Api::where( 'app_code', @$_GET[ 'app_code' ] )->get();
        return $api;
    }

    /**
     * Request Access to an account
     * @return void
    **/

    public function googleRequest()
    {
        return redirect( 'https://accounts.google.com/o/oauth2/v2/auth' . 
        '?scope=' . urlencode( 'https://www.googleapis.com/auth/cloudprint' ) .
        '&access_type=offline' .
        '&include_granted_scopes=true' .
        '&state=state_parameter_passthrough_value' .
        '&redirect_uri=' . urlencode( url( 'api/google-callback' ) ) .
        '&response_type=code' .
        '&client_id=' . config( 'nexopos.server.client_id' ) );
    }

    /**
    * When receiving a code from google
    * @return array procees status
    **/

    public function googleCallback()
    {
        $api                    =   Api::find( session( 'app_id' ) );
        
        if( $api ) {
            $api->google_code       =   $_GET[ 'code' ];
            $api->save();

            // exchange code with access token
            $request                =   json_decode( $this->googleAccessToken( $api->app_code ), true );

            if( @$request[ 'access_token' ] ) {
                $api->google_access_token   =   $request[ 'access_token' ];
                $api->google_token_type     =   $request[ 'token_type' ];
                $api->google_token_expire   =   Carbon::now()->addSeconds( $request[ 'expires_in' ] )->toDateTimeString();

                if( @$request[ 'refresh_token' ] != null ) {
                    $api->google_refresh_token  =   $request[ 'refresh_token' ];
                }

                $api->save();   

                session()->forget( 'app_id' );

                return $this->googleSaveApp( $api->id );
            }
            return $request;
        }

        return [
            'status'    =>  'failed',
            'message'   =>  'session_expired'
        ];
    }

    /**
     * get refresh and access token
     * @param any api code or api array
     * @return json
    **/

    public function googleAccessToken( $api )
    {
        if( ! is_array( $api ) ) {
            $api        =   Api::code( $api )->first()->toArray();
        }
        
        return Curl::to( 'https://www.googleapis.com/oauth2/v4/token' )
        ->withHeader('Content-Type: application/x-www-form-urlencoded')
        ->withData([
            'code'          =>  $api[ 'google_code' ],
            'client_id'     =>  config( 'nexopos.server.client_id' ),
            'client_secret' =>  config( 'nexopos.server.client_secrent' ),
            'grant_type'    =>  'authorization_code',
            'redirect_uri'  =>  url( 'api/google-callback' )
        ])->post();
    }

    /**
     * Refresh Access Token
     * @return json
    **/

    public function googleRefresh() 
    {
        $query    =   Api::code( @$_GET[ 'app_code' ] );
        
        if( $query->count() > 0 ) {
            $api                =   $query->first()->toArray();
            $request            =   json_decode( Curl::to( 'https://www.googleapis.com/oauth2/v4/token' )
            ->withHeader('Content-Type: application/x-www-form-urlencoded')
            ->withData([
                'refresh_token' =>  $api[ 'google_refresh_token' ],
                'client_id'     =>  config( 'nexopos.server.client_id' ),
                'client_secret' =>  config( 'nexopos.server.client_secrent' ),
                'grant_type'    =>  'refresh_token'
            ])->post(), true );

            if( ! empty( $request[ 'access_token' ] ) ) {
                Api::where( 'app_code', $_GET[ 'app_code' ] )
                ->update([
                    'google_refresh_token'  =>  $request[ 'access_token' ],
                    'google_token_expire'   =>  Carbon::now()->addSeconds( $request[ 'expires_in' ] )->toDateTimeString()
                ]);
            }            

            return $request;
        }

        return [
            'status'    =>  'failed',
            'message'   =>  'unknowApp'
        ];
    }

    /**
     * Save Google App
     * @param any app identifier
     * @param string type
     * @return void
    **/

    public function googleSaveApp( $identifier, $type = 'id' )
    {
        if( $type == 'id' ) {
            $query  =   Api::find( $identifier );
        } else {
            $query  =   Api::where( $type, $identifier );
        }

        if( $query ) {
            $api            =   $query->toArray();
            $app_namespace  =   date( 'Ymz-hms' ) . rand(0, 9) . rand(0, 9) . rand(0, 9);

            $api            =   Api::find( $api[ 'id' ] );
            $api->app_code  =   $app_namespace;
            $api->save();

            return redirect( $api->request_uri . '?app_code=' . $api->app_code );
        } 

        return redirect( 'error/404/unknowApp' );
    }

    /**
     * List Google Cloud Printer
     * @param int app id
     * @return json
    **/

    public function googleCloudPrinterList()
    {
        $query            =   Api::where( 'app_code', @$_GET[ 'app_code' ] )->first();

        if( $query != null ) {
            $api            =   $query->toArray();
            return $printers       =   Curl::to( 'https://www.google.com/cloudprint/list' )
            ->withHeader( 'Authorization: OAuth ' . $api[ 'google_access_token' ] )
            ->withHeader( 'X-CloudPrint-Proxy: NexoPOS' )
            ->withData([ 'proxy' => $api[ 'gcp_proxy' ] ] )
            ->post();  
              
        } else {
            return redirect( 'error/404/app' );
        }
    }

    /**
     * Submit Print Job
     * @param string printer id
     * @return json
    **/

    public function googleCloudSubmitPrintJob( $printer_id )
    {
        $query            =   Api::where( 'app_code', $_GET[ 'app_code' ] )->first();

        if( ! empty( $query ) ) {
            $api            =   $query->toArray();
            return Curl::to( 'https://www.google.com/cloudprint/submit' )
            ->withHeader( 'Authorization: OAuth ' . $api[ 'google_access_token' ] )
            ->withHeader( 'X-CloudPrint-Proxy: NexoPOS' )
            ->withData([
                'printerid' =>  $printer_id,
                'title'     =>  request( 'title' ),
                'ticket'    =>  [
                    'version'   =>  '1.0',
                    'print'     =>  [
                        'page_orientation'  =>  [ 'type' => 'LANDSCAPE' ],
                        'color'             =>  [ 'vendor_id' => 1, 'type'  => 'STANDARD_MONOCHROME' ]
                    ]
                ],
                'contentType'               =>  'text/html',
                'content'                   =>  request( 'content' )
            ])->post();
        }
        
    }

    /**
     * Revoke App access to google
     * @return json
    **/

    public function googleRevoke()
    {
        $query            =   Api::code( @$_GET[ 'app_code' ] )->first();

        if( $query ) {
            $api        =   $query->toArray();

            if( ! empty( $api[ 'google_refresh_token' ] ) ) {
                $query      =   Curl::to( 'https://accounts.google.com/o/oauth2/revoke' )
                ->withData([ 'token' => $api[ 'google_refresh_token' ] ] )
                ->get(); 
            } else {
                $query      =   Curl::to( 'https://accounts.google.com/o/oauth2/revoke' )
                ->withData([ 'token' => $api[ 'google_access_token' ] ] )
                ->get(); 
            }

            if( ! empty( @$_GET[ 'request_uri' ] ) ) {
                return redirect( urldecode( $_GET[ 'request_uri' ] ) );
            }

            return $query;
        }

        if( ! empty( @$_GET[ 'request_uri' ] ) ) {
            return redirect( urldecode( $_GET[ 'request_uri' ] ) );
        }

        return [
            'status'        =>  'failed',
            'message'       =>  'unknowApp'
        ];        
    }

    /**
     * Envato Licence Check
    **/

    public function envatoCheck( $licence, $app_name ) {
        $query  =   json_decode( Curl::to( 'https://marketplace.envato.com/api/edge/blair_jersyer/5gpszcw93ufutpqb0q8ors1v9znclaf4/verify-purchase:' . $licence . '.json')
        ->withHeader( 'User-Agent:Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.36' )
        ->get(), true );

        if( !empty( $query[ 'verify-purchase' ] ) ) {
            // It's nexopos licence
            if( in_array( $query[ 'verify-purchase' ][ 'item_id' ], [ "16195010", "20242963" ] ) ) {
                return true;
            }   

            return false;
        }

        return false;
    }
}
