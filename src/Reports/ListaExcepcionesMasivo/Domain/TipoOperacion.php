<?php

namespace AMovil\Reports\ListaExcepcionesMasivo\Domain;

class TipoOperacion
{
    const INGRESAR_LISTA = 1;
    const RETIRAR_LISTA = 2;

    public static function isValid(?int $id): bool {
        return self::INGRESAR_LISTA === $id || self::RETIRAR_LISTA === $id;
    }
}
