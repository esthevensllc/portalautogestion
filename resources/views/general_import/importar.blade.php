<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <div class="card">
        <div class="card-body">
            <form id="form_export">
                <div class="row" style="margin-bottom: 10px;">
                    @csrf
                    <div class="col-lg-3 col-md-4">
                        <div class="form-group">
                            <label for="">Archivo</label>
                            <input type="file" accept="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" name="excel" style="font-size: 0.85rem;" required>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-3" style="display: flex; align-items: end;">
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary btn-sm btn_export">Importar</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
    @include('includes.utils_js')
    <script>
        const onSubmitHandler = (e) => {
            e.preventDefault(e);
    
            let data = new FormData(e.target);
    
            fetch(`{{ asset('general-import/import') }}`, {
                method: 'POST',
                body: data
            })
            .then(response => {
                if(!response.ok){
                    throw new Error(response.statusText);
                }
                return response;
            })
            .then(response => response.json())
            .then(response => {
                console.log(response);
                alert('Importación exitosa');
            })
            .catch(error => {
                alert(error);
            });
        }
    
        document.getElementById('form_export').addEventListener('submit', onSubmitHandler, false);
    </script>
</body>
</html>