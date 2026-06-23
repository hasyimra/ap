<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dev Login — AP</title>
    <link rel="stylesheet" href="{{ asset('vendor/dkm-ui/cuba/css/vendors/bootstrap.css') }}">
</head>
<body class="p-4">
<div class="container" style="max-width: 600px;">
    <h3 class="mb-3">Dev Login — Accounts Payable (ap)</h3>
    <p class="text-muted">Halaman ini hanya aktif di environment local, untuk pengujian sebelum app didaftarkan ke SSO sungguhan.</p>
    <div class="list-group">
        @foreach($users as $user)
            <form method="POST" action="{{ route('dev-login.login', $user) }}" class="list-group-item d-flex justify-content-between align-items-center">
                @csrf
                <div>
                    <strong>{{ $user->name }}</strong>
                    <span class="badge bg-{{ $user->role_color }} ms-2">{{ $user->role_label }}</span>
                    <div class="text-muted small">{{ $user->email }}</div>
                </div>
                <button type="submit" class="btn btn-sm btn-primary">Login</button>
            </form>
        @endforeach
    </div>
</div>
</body>
</html>
