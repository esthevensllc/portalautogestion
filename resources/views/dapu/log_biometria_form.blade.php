<div class="col-lg-3 col-md-4">
    <div class="form-group">
        <label for="">DNI</label>
        <input type="text" name="dni" class="form-control form-control-sm" required>
        <div class="invalid-feedback d-block text-dark">
            Ej. 73318713
        </div>
    </div>
</div>
<div class="col-lg-3 col-md-4">
    <div class="form-group">
        <label for="">Periodo</label>
        <input type="date" name="periodo_date" class="form-control form-control-sm">
    </div>
</div>
<div class="col-lg-3 col-md-4">
    <div class="form-group">
        <label for="hora">Hora (Entre 00 y 23) <span style="color: red;">*</span></label>      
        <input type="number" class="form-control form-control-sm" name="periodo_hour" min="0" max="23" placeholder="Entre 00 - 23">        
        <small style="color: gray;">* Campo opcional</small> 
    </div>
</div>