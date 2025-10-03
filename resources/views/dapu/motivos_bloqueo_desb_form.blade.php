<div class="col-lg-2">
    <div class="form-group">
        <label for="">Tipo input</label>
        <select class="form-control form-control-sm" name="tipo_input" id="tabs_select">
            <option value="1">LINEA</option>
            <option value="2">IMEI</option>
        </select>
    </div>
</div>
<div class="col-lg-8 tabs" data-tab-target="tabs_select">
    <div class="row">
        <div class="col-lg-3 col-md-4 tab-item" data-tab-target="1">
            <div class="form-group">
                <label for="">LINEA</label>
                <input type="text" name="linea" class="form-control form-control-sm" required>
                <div class="invalid-feedback d-block text-dark">
                    Ej. 947158416
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-4 tab-item" data-tab-target="2">
            <div class="form-group">
                <label for="">IMEI</label>
                <input type="number" name="imei" class="form-control form-control-sm" required disabled>
                <div class="invalid-feedback d-block text-dark">
                    Ej: 35035733044914, ingresar los primeros 14 digitos de la izquierda.
                </div>
            </div>
        </div>
    </div>
</div>