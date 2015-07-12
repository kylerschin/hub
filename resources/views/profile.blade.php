<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
@yield('extra_meta')
<title>Hub</title>
<link href="/assets/css/fonts.css" rel="stylesheet">
<link href="/assets/css/font-awesome.css" rel="stylesheet">
<link href="/assets/css/bootstrap.css" rel="stylesheet">
<link href="/assets/css/bootcards-desktop.css" rel="stylesheet">
<link href="/assets/css/profile.css" rel="stylesheet">
<link href="/assets/css/app.css" rel="stylesheet">
<meta name="csrf-token" content="{{ csrf_token() }}" />
</head>
<body>
@include('partials.nav')

<div class="container main">
@yield('content')
</div>
<div class="modal fade" id="followModal" tabindex="-1" role="dialog" aria-labelledby="followModalLabel" aria-hidden="true">
<div class="modal-dialog">
<div class="modal-content">
<div class="modal-header">
<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
<h4 class="modal-title" id="followModalLabel">Successfully subscribed</h4>
</div>
<div class="modal-body">
<p class="lead">You have successfully subscribed to {{{$n->addr}}}!</p>
</div>
<div class="modal-footer">
<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
</div>
</div>
</div>
</div>
</div>
</div>
@include('partials.footer')
<script>
    var nodeIp = "{{{ $n->addr }}}";
    var remAddr = "{{ \Req::ip() }}";
    var clEnv = "d";
</script>
<script src="/assets/js/jquery.min.js"></script>
<script src="/assets/js/bootstrap.min.js"></script>
<script src="/assets/js/bootcards.js"></script>
<script src="/assets/js/jquery.autocomplete.min.js"></script>
<script src="/assets/js/jquery.timeago.min.js"></script>
<script src="/assets/js/profile.js"></script>
@yield('subjs')
</body>
</html>
