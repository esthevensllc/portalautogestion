<div class="col-lg-2">
    <div class="form-group">
        <label for="">Tipo input</label>
        <select class="form-control form-control-sm" name="tipo_input" id="tabs_select">
            <option value="1">MSISDN</option>
            <option value="2">NUMERO DE DOCUMENTO</option>
            <option value="3">CSV MSISDN</option>
            <option value="4">CSV NUMERO DE DOCUMENTO</option>
        </select>
    </div>
</div>
<div class="col-lg-8 tabs" data-tab-target="tabs_select">
    <div class="row">
        <div class="col-lg-3 col-md-4 tab-item" data-tab-target="1">
            <div class="form-group">
                <label for="">MSISDN</label>
                <input type="text" name="msisdn" class="form-control form-control-sm">
                <div class="invalid-feedback d-block text-dark">
                    Puede ingresar mas de un msisdn separado por comas
                    <br>Ej: 941005552, ingresar el numero de servicio movil sin el 51.
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-4 tab-item" data-tab-target="2">
            <div class="form-group">
                <label for="">Número de documento</label>
                <input type="text" name="num_documento" class="form-control form-control-sm">
                <div class="invalid-feedback d-block text-dark">
                    Puede ingresar mas de un numero de documento separado por comas
                    <br>Ej. 20538595188
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-4 tab-item" data-tab-target="3">
            <div class="form-group">
                <label for="">CSV MSISDN</label>
                <input type="file" name="file_msisdn" class="d-block" accept=".csv">
                <div class="invalid-feedback d-block text-dark">
                    Subir el archivo en formato csv sin cabeceras y con los msisdn en la primera columna
                    <br>Ej. 941005552
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-4 tab-item" data-tab-target="4">
            <div class="form-group">
                <label for="">CSV Número de documento</label>
                <input type="file" name="file_num_documento" class="d-block" accept=".csv">
                <div class="invalid-feedback d-block text-dark">
                    Subir el archivo en formato csv sin cabeceras y con los valores en la primera columna
                    <br>Ej. 20538595188
                </div>
            </div>
        </div>
    </div>
</div>