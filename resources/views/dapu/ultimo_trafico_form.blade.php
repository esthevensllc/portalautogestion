<div class="col-lg-2">
    <div class="form-group">
        <label for="">Tipo input</label>
        <select class="form-control form-control-sm" name="tipo_input" id="tabs_select">
            <option value="1">MSISDN</option>
            <option value="2">IMEI</option>
            <option value="3">CSV MSISDN</option>
            <option value="4">CSV IMEI</option>
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
                    <br>Ej: 51987433405, ingresar el numero de servicio movil con el 51.
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-4 tab-item" data-tab-target="2">
            <div class="form-group">
                <label for="">IMEI</label>
                <input type="text" name="imei" class="form-control form-control-sm">
                <div class="invalid-feedback d-block text-dark">
                    Puede ingresar mas de un imei separado por comas
                    <br>Los imeis deberan estar completos
                    <br>Ej. 8689640600906884
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-4 tab-item" data-tab-target="3">
            <div class="form-group">
                <label for="">CSV MSISDN</label>
                <input type="file" name="file_msisdn" class="d-block" accept=".csv">
                <div class="invalid-feedback d-block text-dark">
                    Subir el archivo en formato csv sin cabeceras y con los msisdn en la primera columna
                    <br>Ej. 51987433405
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-4 tab-item" data-tab-target="4">
            <div class="form-group">
                <label for="">CSV IMEI</label>
                <input type="file" name="file_imei" class="d-block" accept=".csv">
                <div class="invalid-feedback d-block text-dark">
                    Subir el archivo en formato csv sin cabeceras y con los imeis en la primera columna
                    <br>Los imeis deberan estar completos
                    <br>Ej. 8689640600906884
                </div>
            </div>
        </div>
    </div>
</div>