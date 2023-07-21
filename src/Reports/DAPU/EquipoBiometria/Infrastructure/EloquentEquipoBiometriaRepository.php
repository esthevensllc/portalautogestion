<?php

namespace AMovil\Reports\DAPU\EquipoBiometria\Infrastructure;

use AMovil\Reports\DAPU\EquipoBiometria\Domain\EquipoBiometriaRepository;
use Illuminate\Support\Facades\DB;

class EloquentEquipoBiometriaRepository implements EquipoBiometriaRepository
{
    public function getByDni_Fono_Periodo($dni, $fono, $periodo)
    {
        $query = "select 
        a.biometric_date,
        a.product_number as phone,
        a.ICCID, 
        a.LECTOR_SERIAL_NUMBER,
        a.agreement_mode_desc,
        a.system_code, 
        a.OPERATION_TYPE_SC, 
        a.OPERATION_TYPE_DESC, 
        a.VALIDATION_TYPE, 
        a.BIOMETRIC_PDV_DESC, 
        a.BIOMETRIC_PDV_CHANNEL_DESC, 
        a.IS_MOBILE_DESC, 
        a.CUSTOMER_DOCUMENT_TYPE_DESC, 
        a.CUSTOMER_ID_CARD_VALUE
        from dwa.dw_t_biometrics a
        where CUSTOMER_ID_CARD_VALUE = ?
        and product_number = ?";

        $values = [$dni, $fono];

        if($periodo !== null){
            $query .= " and to_char(a.biometric_date, 'yyyymm') = ?";
            $values[] = $periodo;
        }

        return DB::select(DB::raw($query), $values);
    }
}
