@extends(backpack_view('blank'))

@section('after_styles')
<style>
    .ui-datepicker-calendar {
        display: none;
    }
    .form-date{
        display: inline-block;
        padding: 10px;
    }

    .disabled{
        cursor: not-allowed;
        pointer-events: none;
    }
    .w-5 {
        width: 20px;
        height: 20px;
    }
    .disabled-label{
        opacity: 0.5;
        pointer-events:none;
    }
</style>
@endsection



@section('header')
<div id="spinnerData" class="modal" data-backdrop="static" data-keyboard="false">
    <div class="cssload-loader-inner">
        <div class="cssload-cssload-loader-line-wrap-wrap">
            <div class="cssload-loader-line-wrap"></div>
        </div>
        <div class="cssload-cssload-loader-line-wrap-wrap">
            <div class="cssload-loader-line-wrap"></div>
        </div>
        <div class="cssload-cssload-loader-line-wrap-wrap">
            <div class="cssload-loader-line-wrap"></div>
        </div>
        <div class="cssload-cssload-loader-line-wrap-wrap">
            <div class="cssload-loader-line-wrap"></div>
        </div>
        <div class="cssload-cssload-loader-line-wrap-wrap">
            <div class="cssload-loader-line-wrap"></div>
        </div>
    </div>
</div>
@endsection

@section('content')
    <section class="content-header">
        <div class="container-fluid mb-3">
            <div class="row">
                
                <div class="col-sm-12">

                    <h3>REPORTE SIGREI</h3>
                    <br>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <div class="dt-buttons btn-group d-xs-block d-sm-inline-block d-md-inline-block d-lg-inline-block" style="vertical-align: top;">
                        <h5>Subir Reporte</h5>    
                        <div class="btn-group" style="margin-right:2px; vertical-align: top;">
                            <form action="{{ url('reporte_sigrei/import') }}" method="post" enctype="multipart/form-data" id="formLoadTemplate">
                                @csrf
                                <label class="btn btn-danger btn-sm" style="cursor:pointer"> 
                                    <input class="btn-importData" type="file" value="Importar" name="fileImport" style="display:none">
                                    <span>
                                    <i class="la la-upload"></i>
                                    Cargar File
                                    </span>
                                </label>
                            </form>
                            <label class="btn-danger btn-sm ml-3 disabled-label exportData" style="cursor:pointer" aria-disabled="true">
                                <span>
                                <i class="la la-download"></i>
                                Descargar File
                                </span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        <div>
    </section>
@endsection

@section('after_scripts')
<script>
    $(document).ready(function () {
        $(".btn-importData").change(function () {
            $("#formLoadTemplate").submit();
            $("#spinnerData").show();
        });
        $(".exportData").click(function () {
            //window.location.href='/portalregulatorio/reporte_sigrei/export';
            $("#spinnerData").show();
            saveOrOpenBlob("{{asset('reporte_sigrei/export')}}", 'reporte_sigrei.xlsx');    
        });

        @if(Session::has('message'))
            $("#spinnerData").hide();
            new Noty({
                text: "{{Session::get('message')}}",
                type: "success"
            }).show();

            $('label').removeClass('disabled-label');
        @endif
    });

    function saveOrOpenBlob(url, blobName) {
        var blob;
        var xmlHTTP = new XMLHttpRequest();
        xmlHTTP.open('GET', url, true);
        xmlHTTP.responseType = 'arraybuffer';
        xmlHTTP.onload = function(e) {
            blob = new Blob([this.response]);   
        };
        xmlHTTP.onprogress = function(pr) {
            $("#spinnerData").show();
        };
        xmlHTTP.onloadend = function(e){
            var fileName = blobName;
            var tempEl = document.createElement("a");
            document.body.appendChild(tempEl);
            tempEl.style = "display: none";
            url = window.URL.createObjectURL(blob);
            tempEl.href = url;
            tempEl.download = fileName;
            tempEl.click();
            window.URL.revokeObjectURL(url);
            $("#spinnerData").hide();
        }
        xmlHTTP.send();
    }
</script>
@endsection