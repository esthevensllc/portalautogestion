@extends(backpack_view('base/layouts/plain'))

@section('after_styles')
@include('includes.datatables_css')
@endsection

@section('content')
<div style="align-items: center; justify-content: center; display: flex; min-height: 500px; flex-direction: column;">
    <div style="max-width: 400px;">
        <h4>Limite de sesiones activas alcanzado</h4>
        <p class="mb-4">{{ $message }}</p>
        <table class="table table-sm table-bordered">
            <thead class="table-secondary">
                <tr>
                    <th>Ip Address</th>
                    <th>Última Actividad</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($sessions as $row)
                    <tr>
                        <td>{{ $row->ip_address }}</td>
                        <td>{{ $row->last_activity }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <a class="btn btn-danger" href="{{ asset("/") }}">Continuar</a>
    </div>
</div>
@endsection