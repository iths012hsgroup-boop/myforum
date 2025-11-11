<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>Aplikasi Admin HS Group</title>
  <!-- Tell the browser to be responsive to screen width -->
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Font Awesome -->
  <link rel="stylesheet" href="{{asset('assets/admin/css/all.min.css')}}" media="all">
  <!-- overlayScrollbars -->
  <link rel="stylesheet" href="{{asset('assets/admin/css/adminlte.min.css')}}">
  <!-- CSS Custom -->
  <link rel="stylesheet" href="{{asset('assets/admin/css/app.css')}}">

  <link rel="shortcut icon" type="image/jpg" href="{{ asset('favicon.png') }}"/>
  <!-- Google Font: Source Sans Pro -->
  <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700" rel="stylesheet">
</head>
<body class="hold-transition login-page">
    @if ($errors->any())
          <div class="alert alert-danger">
              <ul>
                  @foreach ($errors->all() as $error)
                      <li>{{ $error }}</li>
                  @endforeach
              </ul>
          </div>
      @endif
      @if (session('danger'))
          <div class="alert alert-danger">
              {{ session('danger') }}
          </div>
      @endif
      @if (session('danger-with-link'))
          <div class="alert alert-danger">
              {!! session('danger-with-link') !!}
          </div>
      @endif
      @if (session('success'))
          <div class="alert alert-success">
              {{ session('success') }}
          </div>
      @endif
    <div class="login-box">
        <div class="card">
            <img src="{{asset('assets/admin/img/Logo-HSGROUP.png')}}" alt="Logo HSGroup" id="logodashboard">
            {{ html()->form('POST', '/process')->open() }}
            <div class="card-body login-card-body">
                <p class="login-box-msg">silakan Masuk Aplikasi</p>
                <div class="input-group mb-3">
                    <input type="text" class="form-control" placeholder="Username" name="username">
                    <div class="input-group-append">
                        <div class="input-group-text">
                            <span class="fas fa-envelope"></span>
                        </div>
                    </div>
                </div>
                <div class="input-group mb-3">
                    <input type="password" class="form-control" placeholder="Password" name="password" autocomplete="off">
                    <div class="input-group-append">
                        <div class="input-group-text">
                            <span class="fas fa-lock"></span>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-4">
                        <button type="submit" class="btn btn-primary btn-block">Masuk</button>
                    </div>
                </div>
            </div>
            {{ html()->form()->close() }}
        </div>
    </div>

<!-- jQuery -->
<script src="{{asset('assets/admin/js/jquery.min.js')}}"></script>
<!-- Bootstrap 4 -->
<script src="{{asset('assets/admin/js/bootstrap.bundle.min.js') }}"></script>
<!-- AdminLTE App -->
<script src="{{ asset('assets/admin/js/adminlte.min.js') }}"></script>

@stack('scripts')
</body>
</html>
