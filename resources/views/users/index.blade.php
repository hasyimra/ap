@extends('layouts.admin')

@section('title', 'Users')
@section('breadcrumb', 'Users')

@section('content')
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Manajemen User &amp; Role</h5>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Login Terakhir</th>
                            @canManageUsers
                                <th>Aksi</th>
                            @endcanManageUsers
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($users as $user)
                            <tr>
                                <td>{{ $user->name }}</td>
                                <td>{{ $user->email }}</td>
                                <td><span class="badge bg-{{ $user->role_color }}">{{ $user->role_label }}</span></td>
                                <td>{{ $user->is_active ? 'Aktif' : 'Nonaktif' }}</td>
                                <td>{{ $user->last_login_at?->format('d/m/Y H:i') ?? '-' }}</td>
                                @canManageUsers
                                    <td>
                                        <form method="POST" action="{{ route('users.update', $user) }}" class="d-flex gap-2">
                                            @csrf
                                            @method('PUT')
                                            <select name="role" class="form-select form-select-sm" style="width:auto">
                                                @foreach(\App\Models\User::roleOptions() as $value => $label)
                                                    <option value="{{ $value }}" @selected($user->role === $value)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                            <input type="hidden" name="is_active" value="{{ $user->is_active ? 1 : 0 }}">
                                            <button type="submit" class="btn btn-sm btn-outline-primary">Simpan</button>
                                        </form>
                                    </td>
                                @endcanManageUsers
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $users->links() }}
        </div>
    </div>
@endsection
