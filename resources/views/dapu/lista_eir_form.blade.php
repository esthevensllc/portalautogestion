<div class="col-lg-2">
    <div class="form-group">
        <label for="">Tipo input</label>
        <select class="form-control form-control-sm" name="tipo_input" id="tabs_select">
            <option value="1">IMEI</option>
            <option value="2">CSV IMEI</option>
        </select>
    </div>
</div>
<div class="col-lg-8 tabs" data-tab-target="tabs_select">
    <div class="row">
        <div class="col-lg-3 col-md-4 tab-item" data-tab-target="1">
            <div class="form-group">
                <label for="">IMEI</label>
                <input type="text" name="imei" class="form-control form-control-sm" required>
                <div class="invalid-feedback d-block text-dark">
                    Puede ingresar mas de un imei separado por comas
                    <br>Ej: 35468681245423, ingresar los primeros 14 digitos de la izquierda.
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-4 tab-item" data-tab-target="2">
            <div class="form-group">
                <label for="">CSV IMEI</label>
                <input type="file" name="file" class="" required disabled>
                <div class="invalid-feedback d-block text-dark">
                    Subir el archivo en formato csv sin cabeceras y con los datos en la primera columna
                    <br>Ej: 35468681245423, ingresar los primeros 14 digitos de la izquierda.
                </div>
            </div>
        </div>
    </div>
</div>