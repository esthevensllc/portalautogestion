@extends(backpack_view('blank'))

@section('content')

<h4 style="">{{ $config["title"] }}</h4>
<div class="card">
    <div class="card-body">
        <form id="form_export">
            @csrf
            <div class="row">
                <div class="col-lg-3 col-md-4 form-group">
                    <label for="">Excel</label>
                    <input type="file" class="d-block" name="excel" required>
                </div>
                <div class="col-lg-12 form-group">
                    <button type="submit" class="btn btn-primary btn-sm btn_export">Cargar archivo</button>
                </div>
            </div>
        </form>
    </div>
</div>
@include('includes.spinner_loader')
@endsection

@section('after_scripts')
@include('includes.utils_js')
@include('includes.noty_js')
<script>
$(function() {
    const config = @json($config);

    document.querySelector("#form_export")
    .addEventListener("submit", function(e){
        e.preventDefault();
        const data = new FormData(e.target);
        fetch(config.api, {method: 'POST', body: data, headers: {"Accept": "application/json"}})
        //.then(resp => response.json())
        .then(async(response) => {
            if(!response.ok){
                let json_response = await response.json();
                throw new Error(json_response.message);
            }
            
            new Noty({
                type: 'success',
                layout: 'topRight',
                text: "Cargado correctamente"
            }).show();
        })
        .catch(error => {
            alert(error);
        });
    });
});
</script>
@endsection