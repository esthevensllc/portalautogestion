@extends(backpack_view('blank'))

@section('after_styles')
@include('includes.datatables_css')
@endsection

@section('content')
<h4 style="">{{ $config["title"] }}</h4>
<div class="card">
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <form id="search_form">
                    @csrf
                    <div class="input-group mb-3">
                        <input type="text" class="form-control" id="imeiText" placeholder="Ingresar valores (123,124)" aria-label="Búsqueda" aria-describedby="btnBuscar" name="value" required>
                        <div class="input-group-append">
                            <select class="form-control" name="filter" required>
                                @foreach ($config["fields"] as $field)
                                    @if(isset($field["isFilter"]))
                                        <option value="{{ $field['id'] }}">{{ $field["label"] }}</option>
                                    @endif
                                @endforeach
                            </select>
                            <button class="btn btn-outline-danger" type="submit">Buscar</button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="col-md-6">
                <form id="search_form_file">
                    <div class="input-group mb-3">
                        <input type="file" name="value" style="flex: 1;" required>
                        <div class="input-group-append">
                            <select class="form-control" name="filter" required>
                                @foreach ($config["fields"] as $field)
                                    @if(isset($field["isFilter"]))
                                        <option value="{{ $field['id'] }}">{{ $field["label"] }}</option>
                                    @endif
                                @endforeach
                            </select>
                            <button class="btn btn-outline-danger" type="submit">Buscar</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<table
    class="bg-white table table-striped table-hover nowrap rounded shadow-xs border-xs mt-2 w-100 tbl-tripleta table-sm" cellspacing="0"
    {{-- data-responsive-table="{{ (int) $crud->getOperationSetting('responsiveTable') }}"
    data-has-details-row="{{ (int) $crud->getOperationSetting('detailsRow') }}"
    data-has-bulk-actions="{{ (int) $crud->getOperationSetting('bulkActions') }}" --}}
>
    <thead class="bg-danger">
        <tr>
            @foreach ($config["fields"] as $field)
                <th>{{ $field["label"] }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
    </tbody>
</table>
@include('includes.spinner_loader')
@endsection

@section('after_scripts')
@include('includes.datatables_js')
@include('includes.utils_js')
<script>
$(function() {
    const config = @json($config);

    let columns = config.fields.map(r => ({data: r["id"]}))

    $("#search_form").on("submit", function(e){
        e.preventDefault();
        const loader_component = document.querySelector('.loader_component');
        const formData = new FormData(e.target);
        loader_component.style.display = 'block';
        utils.fetch(`${config.searchApi}?filter=${formData.get("filter")}&value=${formData.get("value")}`, {
            headers: {
                "Accept": "application/json",
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        })
        .then(async(resp) => {
            const isOk = resp.ok;
            const json = await resp.json();

            if(isOk){
                let html = json.data.map(row => {
                    let rowHtml = config.fields.map(f => `<td>${row[f.id]}</td>`).join("");
                    return `<tr>${rowHtml}</tr>`;
                }).join("");
                $(".tbl-tripleta tbody").html(html);
            }else{
                alert(json.message);
            }

            loader_component.style.display = 'none';
        });
    });

    $("#search_form_file").on("submit", function(e){
        e.preventDefault();
        const loader_component = document.querySelector('.loader_component');
        const formData = new FormData(e.target);
        loader_component.style.display = 'block';
        utils.fetch(`${config.searchApiByFile}`, {
            headers: {
                "Accept": "application/json",
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            method: "POST",
            body: formData
        })
        .then(async(resp) => {
            const isOk = resp.ok;
            const json = await resp.json();

            if(isOk){
                let html = json.data.map(row => {
                    let rowHtml = config.fields.map(f => `<td>${row[f.id]}</td>`).join("");
                    return `<tr>${rowHtml}</tr>`;
                }).join("");
                $(".tbl-tripleta tbody").html(html);
            }else{
                alert(json.message);
            }

            loader_component.style.display = 'none';
        });
    });
});
</script>
@endsection