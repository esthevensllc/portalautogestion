<?php

namespace AMovil\Reports\RepDetLlamadas\Domain;

class ReporteDetalleLlamada
{
    const LIMIT = 800000;
    // const LIMIT = 10;
    private $chunks = [];
    private $count;

    public function __construct(array $chunks)
    {
        $this->chunks = $chunks;
        $this->calcSummary();
    }

    public function calcSummary(){
        $this->count = 0;
        foreach($this->chunks as $row){
            if(array_key_exists("data", $row)){
                $this->count += count($row["data"]);
            }else{
                $this->count += self::LIMIT;
            }
        }
    }

    public function getCount(): int
    {
        return $this->count;
    }

    public function getIterator(){
        foreach($this->chunks as $row){
            if(array_key_exists("data", $row)){
                yield $row["data"];
            }else{
                $data = json_decode(file_get_contents($row["file"]));
                yield $data;
                unlink($row["file"]);
            }
        }
    }
}
