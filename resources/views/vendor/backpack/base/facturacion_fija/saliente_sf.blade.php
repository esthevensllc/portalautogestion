@extends(backpack_view('blank'))

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

<h4 style="">{{$data['title']}}</h4>
<div class="card">
    <div class="card-body">
        <form id="form_export">
            <div class="row">
                <div class="col-lg-2">
                    <div class="form-group">
                        <label for="">Codigo cliente</label>
                        <input type="text" class="form-control form-control-sm" name="cod_cliente" required>
                        <span class="text-black-50 small">Ej. 3848,1333261</span>
                    </div>
                </div>
                <div class="col-lg-2">
                    <div class="form-group">
                        <label for="">Fecha Inicio</label>
                        <input type="text" class="form-control form-control-sm datepicker" name="f_ini" required>
                        <span class="text-black-50 small">Ej. 05-09-2022</span>
                    </div>
                </div>
                <div class="col-lg-2">
                    <div class="form-group">
                        <label for="">Fecha Fin</label>
                        <input type="text" class="form-control form-control-sm datepicker" name="f_fin" required>
                        <span class="text-black-50 small">Ej. 23-09-2022</span>
                    </div>
                </div>
                <div class="col-lg-3" style="display: flex; align-items: center;">
                    <div class="form-group">
                        <button type="submit" class="btn btn-danger btn-sm btn_export">Descargar</button>
                    </div>
                </div>
            </div>
        </form>
        <div>
            <span class="text-danger small ">*TODA CONSULTA QUE SE REALICE SE RESGRISTRARA EN UN LOG.</span>
        </div>
    </div>
</div>

@endsection

@section('after_scripts')
<link rel="stylesheet" type="text/css" href="/portalautogestion_rel/public/packages/bootstrap-datepicker/dist/css/bootstrap-datepicker.min.css">
<script src="/portalautogestion_rel/public/packages/bootstrap-datepicker/dist/js/bootstrap-datepicker.min.js"></script>
<script>
$(function() {
    const config = @json($data);
    const downloadFile = (blob, fileName) => {
        const url = window.URL.createObjectURL(new Blob([blob]))
        const link = document.createElement('a')
        link.href= url
        link.setAttribute('download', fileName);
        document.body.appendChild(link);
        link.click();

        link.parentNode.removeChild(link);
    }
    $('.datepicker').datepicker({
        format: "dd-mm-yyyy",
        language: "es"
      });

    $("#form_export").on('submit', function(e){
        p_error = 0;
        $("#spinnerData").show();
        e.preventDefault();
        $(".btn_export").prop('disabled', true);
        const cod_cliente = $("#form_export input[name=cod_cliente]").val();
        const f_ini = $("#form_export input[name=f_ini]").val();
        const f_fin = $("#form_export input[name=f_fin]").val();

        $.ajax({
            type: "GET",
            url: "{{ url('facturacion-fija/salientes_sin_factura/validReport') }}",
            data: {
                cod_cliente: cod_cliente,
                f_ini: f_ini,
                f_fin: f_fin
                },
            success: function(response)
            {
                var jsonData = JSON.parse(response);

                console.log(jsonData);
  
                // user is logged in successfully in the back-end
                // let's redirect
                if (jsonData != 1)
                {
                    if(jsonData==-1){
                        new Noty({
                            text: "NO EXISTE EL CODIGO DE CLIENTE INGRESADO",
                            type: "error"
                        }).show();
                    }
                    if(jsonData==0){
                        new Noty({
                            text: "NO SE CONTIENE INFORMACION DE ESE CODIGO DE CLIENTE PAR EL PERIODO INGRESADO",
                            type: "error"
                        }).show();
                    }
                    $(".btn_export").prop('disabled', false);
                    $("#spinnerData").hide();
                }
                else
                {
                    fetch("{{ url('facturacion-fija/salientes_sin_factura/export') }}"+`?cod_cliente=${cod_cliente}&f_ini=${f_ini}&f_fin=${f_fin}`, {
                        method: 'GET'
                    })
                    .then(response => {
                        if(!response.ok){
                            throw new Error(response.statusText);
                        }
                        
                        return response;
                                
                    })
                    .then(response => response.blob())
                    .then(response => {
                        let filename = 'Facturacion_Fija_'+cod_cliente+'_'+f_ini+'.xlsx';
                        if(config['filename'] !== undefined){
                            filename = config['filename'];
                        }

                        downloadFile(response , filename);  

                        $(".btn_export").prop('disabled', false);
                        $("#spinnerData").hide();
                    })
                    .catch(error => {
                        $(".btn_export").prop('disabled', false);
                        Promise.reject();
                        $("#spinnerData").hide();
                        //throw(error);
                    });
                }
           }
       });

            
    });
});
</script>
@endsection