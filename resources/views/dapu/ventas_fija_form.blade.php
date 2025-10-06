<div class="col-lg-2">
    <div class="form-group">
        <label for="">Tipo input</label>
        <select class="form-control form-control-sm" name="tipo_input" id="tabs_select">
            <option value="1">NUMERO DE DOCUMENTO</option>
            <option value="2">CSV NUMERO DE DOCUMENTO</option>
            <option value="3">SOT</option>
            <option value="4">CSV SOT</option>
        </select>
    </div>
</div>
<div class="col-lg-8 tabs" data-tab-target="tabs_select">
    <div class="row">
        <div class="col-lg-3 col-md-4 tab-item" data-tab-target="1">
            <div class="form-group">
                <label for="">Número de documento</label>
                <input type="text" name="num_documento" class="form-control form-control-sm">
                <div class="invalid-feedback d-block text-dark">
                    Puede ingresar mas de un numero de documento separado por comas
                    <br>Ej. 09567400
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-4 tab-item" data-tab-target="2">
            <div class="form-group">
                <label for="">CSV Número de documento</label>
                <input type="file" name="file_num_documento" class="d-block" accept=".csv">
                <div class="invalid-feedback d-block text-dark">
                    Subir el archivo en formato csv sin cabeceras y con los valores en la primera columna
                    <br>Ej. 09567400
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-4 tab-item" data-tab-target="3">
            <div class="form-group">
                <label for="">SOT</label>
                <input type="text" name="sot" class="form-control form-control-sm">
                <div class="invalid-feedback d-block text-dark">
                    Puede ingresar mas de un SOT
                    <br>Ej. 17460638
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-4 tab-item" data-tab-target="4">
            <div class="form-group">
                <label for="">CSV SOT</label>
                <input type="file" name="file_sot" class="d-block" accept=".csv">
                <div class="invalid-feedback d-block text-dark">
                    Subir el archivo en formato csv sin cabeceras y con los valores en la primera columna
                    <br>Ej. 17460638
                </div>
            </div>
        </div>
    </div>
</div>