<form class="form-controll" method="post" action="{{ url( '/api/auth' ) }}" >
    {{ csrf_field() }}
    <input type="text" class="form-control" name="host" value="{{ Request::server( 'SERVER_NAME' ) }}">
    <input type="text" class="form-control" name="ip" value="{{ Request::ip() }}">
    <input type="text" class="form-control" name="app_name" value="app.nexopos.com">
    <input type="text" class="form-control" name="app_version" value="3.1">
    <input type="text" class="form-control" name="gcp_proxy" value="405884fb-c551-4c4e-9549-4b68b1d00597">
    <input type="submit">
</form>