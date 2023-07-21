<?php

namespace AMovil\Shared\Infrastructure\Eloquent;

class EloquentCriteriaConverter
{
    public static function fromRawArray($builder, $fields, $filters, $order, $offset=null, $limit=null)
    {
        $operators = [
            'eq' => ":name = ?",
            'ne' => ":name != ?",
            'in' => ":name in (?)",
            'is_null' => ":name is null",
            'not_null' => ":name is not null",
            // oracle
            //'cn' => "lower(:name) like CONCAT('%', lower(?), '%')",
            'cn' => "lower(:name) like '%'|| lower(?) ||'%'",
            'lt' => ":name < ?",
            'gt' => ":name > ?",
        ];
        $ok_filters = [];
        $fields_keys = array_keys($fields);

        foreach($filters as $filter){
            $parts = explode(".", $filter);
            if(in_array($parts[0], $fields_keys)){
                $ok_filters[] = $filter;
            }
        }

        foreach($ok_filters as $filter){
            $parts = explode(".", $filter);
            if(count($parts) >= 3){
                $name = $parts[0];
                $op = $parts[1];
                $value = implode(".", array_splice($parts, 2));
                $template = $operators[$op];
                if($fields[$name]['type'] === 'datetime'){
                    $template = str_replace("?", "TO_DATE(?, 'YYYY-MM-DD')", $template);
                }
                if($op !== 'in' && $op !== 'is_null' && $op !== 'not_null'){
                    //$builder->where($name, $operators[$op], $value);
                    $builder->where(function($query) use ($name, $operators, $op, $value){
                        $query->whereRaw(str_replace(":name", $name, $operators[$op]), $value);
                    });
                }else if ($op === 'is_null' || $op === 'not_null'){
                    $builder->where(function($query) use ($name, $operators, $op, $value){
                        $query->whereRaw(str_replace(":name", $name, $operators[$op]));
                    });
                }else{
                    $builder->whereIn($name, explode(",", $value));
                }

            }
        }

        if($offset !== null && $limit !== null){
            $builder->limit($limit)->offset($offset);
        }

        if(count($order) > 0){
            $builder->orderBy($order[0], $order[1]??'asc');
        }
    }
}
