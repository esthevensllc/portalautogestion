<div class="col-lg-2">
    <div class="form-group">
        <label for="">Tipo input</label>
        <select class="form-control form-control-sm" name="tipo_input" id="tabs_select">
            {{-- <option value="1">Periodos</option> --}}
            <option value="1">LINEA</option>
            <option value="2">CSV LINEAS</option>
        </select>
    </div>
</div>
<div class="col-lg-8 tabs" data-tab-target="tabs_select">
    <div class="row">
        <div class="col-lg-3 col-md-4 tab-item" data-tab-target="1">
            <div class="form-group">
                <label for="">Linea</label>
                <input type="text" name="linea" class="form-control form-control-sm" required>
                <div class="invalid-feedback d-block text-dark">
                    Puede ingresar mas de una linea separada por comas
                    <br>Anteponer el codigo 51
                    Ej. 51947158416
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-4 tab-item" data-tab-target="2">
            <div class="form-group">
                <label for="">Archivo lineas</label>
                <input type="file" class="d-block" name="file" accept=".csv" required disabled>
                <div class="invalid-feedback d-block text-dark">
                    Subir el archivo en formato csv sin cabeceras y con las lineas en la primera columna
                    <br>Anteponer el codigo 51
                    Ej. 51947158416
                </div>
            </div>
        </div>
    </div>
</div>