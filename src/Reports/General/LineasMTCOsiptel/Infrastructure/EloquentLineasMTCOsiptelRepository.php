<?php

namespace AMovil\Reports\General\LineasMTCOsiptel\Infrastructure;

use AMovil\Auth\AccessControl\Domain\AuthService;
use AMovil\Reports\General\LineasMTCOsiptel\Domain\LineasMTCOsiptelRepository;
use DateTime;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class EloquentLineasMTCOsiptelRepository implements LineasMTCOsiptelRepository
{
    private $connection = "oracle";
    private $userIdentifier;
    private $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function countLineasPrepagoForToday($ticket): int
    {
        $date = new DateTime();
        $result = DB::table("USRAES.SMS_PREPAGO_MTC")
        ->selectRaw("count(*) as counter")
        ->where("fecha", $date->format("Y-m-d"))
        ->where("ticket", $ticket)
        ->first();
        return $result->counter;
    }

    public function countLineasPostpagoForToday($ticket): int
    {
        $date = new DateTime();
        $result = DB::table("USRAES.SMS_POSTPAGO_MTC")
        ->selectRaw("count(*) as counter")
        ->where("fecha", $date->format("Y-m-d"))
        ->where("ticket", $ticket)
        ->first();
        return $result->counter;
    }

    public function getReportePrepago($ticket, $offset=0, $limit = null)
    {
        $this->userIdentifier = $this->authService->getUserIdentifier();
        $date = new DateTime();

        $builder = DB::table("USRAES.SMS_PREPAGO_MTC")
        ->select("MSISDN")
        ->where("fecha", $date->format("Y-m-d"))
        ->where("ticket", $ticket);

        if($limit !== null){
            $builder->limit($limit)->offset($offset);
        }
        return $builder->get();
    }

    public function getReportePostpago($ticket, $offset=0, $limit = null)
    {
        $this->userIdentifier = $this->authService->getUserIdentifier();
        $date = new DateTime();
        
        $builder = DB::table("USRAES.SMS_POSTPAGO_MTC")
        ->select("MSISDN")
        ->where("fecha", $date->format("Y-m-d"))
        ->where("ticket", $ticket);

        if($limit !== null){
            $builder->limit($limit)->offset($offset);
        }
        return $builder->get();
    }

    public function saveLineasPrepagoForToday($ticket)
    {
        $this->userIdentifier = $this->authService->getUserIdentifier();
        $queries = [];
        $queries[] = ["sql" => "BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.SMS_PREPAGO_TMP_{$this->userIdentifier}';
        EXCEPTION
        WHEN OTHERS THEN
            IF SQLCODE != -942 THEN RAISE; END IF;
        END;"];
        $queries[] = ["sql" => "CREATE TABLE USRAES.SMS_PREPAGO_TMP_{$this->userIdentifier}(FECHA DATE,MSISDN VARCHAR2(100))"];
        $queries[] = ["sql" => "BEGIN
            INSERT INTO USRAES.SMS_PREPAGO_TMP_{$this->userIdentifier}
            SELECT TRUNC(SYSDATE, 'DD') FECHA, MSISDN FROM (
                select
                A.Subscription_Access_Number MSISDN
                from dwa.DW_M_SUBSCRIPTION A
                where 
                A.SUBSCRIPTION_SERVICE_CLASS_DESC='TELEFONIA MOVIL'
                AND A.AGREEMENT_STATUS NOT  IN ('D')
                AND A.AGREEMENT_MODE='PREPAGO'
                and substr(a.SUBSCRIPTION_ACCESS_NUMBER,1,3) = '519'
                GROUP BY A.Subscription_Access_Number
            );
            COMMIT;
        END;"];
        $queries[] = ["sql" => "DECLARE
            V_TICKET VARCHAR2(100) := :p_ticket;
        BEGIN
            DELETE FROM USRAES.SMS_PREPAGO_MTC
            WHERE FECHA = (SELECT FECHA FROM USRAES.SMS_PREPAGO_TMP_{$this->userIdentifier} FETCH FIRST '1' ROWS ONLY)
            AND TICKET = V_TICKET;
            COMMIT;

            INSERT INTO USRAES.SMS_PREPAGO_MTC(FECHA, MSISDN, TICKET)
            SELECT FECHA, MSISDN, V_TICKET FROM USRAES.SMS_PREPAGO_TMP_{$this->userIdentifier}
            WHERE MSISDN NOT IN (
                SELECT MSISDN FROM USRAES.SMS_POSTPAGO_MTC WHERE FECHA = TRUNC(SYSDATE, 'DD')
                AND TICKET = V_TICKET
            );
            COMMIT;

            DELETE FROM USRAES.SMS_RESUMEN_LOG
            WHERE FECHA = TRUNC(SYSDATE, 'DD')
            AND TICKET = V_TICKET
            AND TIPO_PLAN = 'PREPAGO'
            AND TIPO_SOLICITUD = 'PCM';
            COMMIT;

            INSERT INTO USRAES.SMS_RESUMEN_LOG(FECHA, TICKET, TIPO_PLAN, TIPO_SOLICITUD)
            VALUES (TRUNC(SYSDATE, 'DD'), V_TICKET, 'PREPAGO', 'PCM');
            COMMIT;

            MERGE INTO USRAES.SMS_MTC_OSIPTEL_HIST A
            USING (
                SELECT
                V_TICKET TICKET, 'PCM' TIPO, TRUNC(SYSDATE, 'DD') FECHA, COUNT(*) AS COUNTER
                FROM USRAES.SMS_PREPAGO_MTC WHERE FECHA = TRUNC(SYSDATE, 'DD') AND TICKET = V_TICKET
            ) B
            ON (A.TICKET = B.TICKET AND A.TIPO = B.TIPO AND A.FECHA = B.FECHA)
            WHEN MATCHED THEN UPDATE SET
                A.NUM_PRE = B.COUNTER
            WHEN NOT MATCHED THEN INSERT (TICKET, TIPO, FECHA, NUM_PRE)
                VALUES(B.TICKET, B.TIPO, B.FECHA, B.COUNTER);
            COMMIT;
        END;", "params" => ["p_ticket" => $ticket]];
        $this->exec_sql($queries);
    }

    public function saveLineasPostpagoForToday($ticket)
    {
        $this->userIdentifier = $this->authService->getUserIdentifier();
        $queries = [];
        $queries[] = ["sql" => "BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.SMS_POSTPAGO_TMP_{$this->userIdentifier}';
        EXCEPTION
        WHEN OTHERS THEN
            IF SQLCODE != -942 THEN RAISE; END IF;
        END;"];
        $queries[] = ["sql" => "CREATE TABLE USRAES.SMS_POSTPAGO_TMP_{$this->userIdentifier}(FECHA DATE,MSISDN VARCHAR2(100))"];
        $queries[] = ["sql" => "BEGIN
            INSERT INTO USRAES.SMS_POSTPAGO_TMP_{$this->userIdentifier}
            select TRUNC(SYSDATE, 'DD') FECHA, MSISDN FROM (
                select
                A.Subscription_Access_Number MSISDN
                from dwa.DW_M_SUBSCRIPTION A
                where 
                A.SUBSCRIPTION_SERVICE_CLASS_DESC='TELEFONIA MOVIL'
                AND A.AGREEMENT_STATUS NOT IN ('D')
                AND A.AGREEMENT_MODE='POSTPAGO'
                and substr(a.SUBSCRIPTION_ACCESS_NUMBER,1,3) = '519'
                GROUP BY A.Subscription_Access_Number
            );
            COMMIT;
        END;"];
        $queries[] = ["sql" => "DECLARE
            V_TICKET VARCHAR2(100) := :p_ticket;
        BEGIN
            DELETE FROM USRAES.SMS_POSTPAGO_MTC
            WHERE FECHA = (SELECT FECHA FROM USRAES.SMS_POSTPAGO_TMP_{$this->userIdentifier} FETCH FIRST '1' ROWS ONLY)
            AND TICKET = V_TICKET;
            COMMIT;

            INSERT INTO USRAES.SMS_POSTPAGO_MTC(FECHA, MSISDN, TICKET)
            SELECT FECHA, MSISDN, V_TICKET FROM USRAES.SMS_POSTPAGO_TMP_{$this->userIdentifier};
            COMMIT;

            DELETE FROM USRAES.SMS_RESUMEN_LOG
            WHERE FECHA = TRUNC(SYSDATE, 'DD')
            AND TICKET = V_TICKET
            AND TIPO_PLAN = 'POSTPAGO'
            AND TIPO_SOLICITUD = 'PCM';
            COMMIT;

            INSERT INTO USRAES.SMS_RESUMEN_LOG(FECHA, TICKET, TIPO_PLAN, TIPO_SOLICITUD)
            VALUES (TRUNC(SYSDATE, 'DD'), V_TICKET, 'POSTPAGO', 'PCM');
            COMMIT;

            MERGE INTO USRAES.SMS_MTC_OSIPTEL_HIST A
            USING (
                SELECT
                V_TICKET TICKET, 'PCM' TIPO, TRUNC(SYSDATE, 'DD') FECHA, COUNT(*) AS COUNTER
                FROM USRAES.SMS_POSTPAGO_MTC WHERE FECHA = TRUNC(SYSDATE, 'DD') AND TICKET = V_TICKET
            ) B
            ON (A.TICKET = B.TICKET AND A.TIPO = B.TIPO AND A.FECHA = B.FECHA)
            WHEN MATCHED THEN UPDATE SET
                A.NUM_POST = B.COUNTER
            WHEN NOT MATCHED THEN INSERT (TICKET, TIPO, FECHA, NUM_POST)
                VALUES(B.TICKET, B.TIPO, B.FECHA, B.COUNTER);
            COMMIT;
        END;", "params" => ["p_ticket" => $ticket]];
        $this->exec_sql($queries);
    }

    // OSIPTEL
    public function getLineasPostpagoOsiptel($ticket, $offset = 0, $limit = null)
    {
        $date = new DateTime();
        
        $builder = DB::table("USRAES.SMS_POSTPAGO_OSIPTEL")
        ->select("MSISDN")
        ->where("fecha", $date->format("Y-m-d"))
        ->where("ticket", $ticket);

        if($limit !== null){
            $builder->limit($limit)->offset($offset);
        }
        return $builder->get();
    }

    public function saveLineasPostpagoOsiptelForToday($ticket)
    {
        $this->userIdentifier = $this->authService->getUserIdentifier();
        $queries = [];
        $queries[] = ["sql" => "BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.SMS_POSTPAGO_OSIPTEL_{$this->userIdentifier}';
        EXCEPTION
        WHEN OTHERS THEN
            IF SQLCODE != -942 THEN RAISE; END IF;
        END;"];
        $queries[] = ["sql" => "CREATE TABLE USRAES.SMS_POSTPAGO_OSIPTEL_{$this->userIdentifier}(FECHA DATE,MSISDN VARCHAR2(100))"];
        $queries[] = ["sql" => "BEGIN
            INSERT INTO USRAES.SMS_POSTPAGO_OSIPTEL_{$this->userIdentifier}
            select TRUNC(SYSDATE, 'DD') FECHA, MSISDN FROM (
                select
                A.Subscription_Access_Number MSISDN
                from dwa.DW_M_SUBSCRIPTION A
                where 
                A.SUBSCRIPTION_SERVICE_CLASS_DESC='TELEFONIA MOVIL'
                AND A.AGREEMENT_STATUS NOT IN ('D')
                AND A.AGREEMENT_MODE='POSTPAGO'
                and substr(a.SUBSCRIPTION_ACCESS_NUMBER,1,3) = '519'
                and A.ID_CARD_VALUE NOT IN
                (
                    SELECT ID_CARD_VALUE from (
                        select c.*,row_number() over (partition by c.ID_CARD_VALUE order by c.SUBSCRIPTION_ACCESS_NUMBER desc) ran
                        from (select * from dwa.DW_M_SUBSCRIPTION) c
                    ) where ran = 11 and ID_CARD_TYPE_VALUE in ('RUC')
                ) GROUP BY A.Subscription_Access_Number
            );
            COMMIT;
        END;"];
        $queries[] = ["sql" => "DECLARE
            V_TICKET VARCHAR2(100) := :p_ticket;
        BEGIN
            DELETE FROM USRAES.SMS_POSTPAGO_OSIPTEL
            WHERE FECHA = (SELECT FECHA FROM USRAES.SMS_POSTPAGO_OSIPTEL_{$this->userIdentifier} FETCH FIRST '1' ROWS ONLY)
            AND TICKET = V_TICKET;
            COMMIT;

            INSERT INTO USRAES.SMS_POSTPAGO_OSIPTEL(FECHA, MSISDN, TICKET)
            SELECT FECHA, MSISDN, V_TICKET FROM USRAES.SMS_POSTPAGO_OSIPTEL_{$this->userIdentifier};
            COMMIT;

            DELETE FROM USRAES.SMS_RESUMEN_LOG
            WHERE FECHA = TRUNC(SYSDATE, 'DD')
            AND TICKET = V_TICKET
            AND TIPO_PLAN = 'POSTPAGO'
            AND TIPO_SOLICITUD = 'OSIPTEL';
            COMMIT;

            INSERT INTO USRAES.SMS_RESUMEN_LOG(FECHA, TICKET, TIPO_PLAN, TIPO_SOLICITUD)
            VALUES (TRUNC(SYSDATE, 'DD'), V_TICKET, 'POSTPAGO', 'OSIPTEL');
            COMMIT;

            MERGE INTO USRAES.SMS_MTC_OSIPTEL_HIST A
            USING (
                SELECT
                V_TICKET TICKET, 'OSIPTEL' TIPO, TRUNC(SYSDATE, 'DD') FECHA, COUNT(*) AS COUNTER
                FROM USRAES.SMS_POSTPAGO_OSIPTEL WHERE FECHA = TRUNC(SYSDATE, 'DD') AND TICKET = V_TICKET
            ) B
            ON (A.TICKET = B.TICKET AND A.TIPO = B.TIPO AND A.FECHA = B.FECHA)
            WHEN MATCHED THEN UPDATE SET
                A.NUM_POST = B.COUNTER
            WHEN NOT MATCHED THEN INSERT (TICKET, TIPO, FECHA, NUM_POST)
                VALUES(B.TICKET, B.TIPO, B.FECHA, B.COUNTER);
            COMMIT;
        END;", "params" => ["p_ticket" => $ticket]];
        $this->exec_sql($queries);
    }

    public function countLineasPostpagoOsiptelForToday($ticket): int
    {
        $date = new DateTime();
        $result = DB::table("USRAES.SMS_POSTPAGO_OSIPTEL")
        ->selectRaw("count(*) as counter")
        ->where("fecha", $date->format("Y-m-d"))
        ->where("ticket", $ticket)
        ->first();
        return $result->counter;
    }


    public function getLineasPrepagoOsiptel($ticket, $offset = 0, $limit = null)
    {
        $date = new DateTime();
        
        $builder = DB::table("USRAES.SMS_PREPAGO_OSIPTEL")
        ->select("MSISDN")
        ->where("fecha", $date->format("Y-m-d"))
        ->where("ticket", $ticket);

        if($limit !== null){
            $builder->limit($limit)->offset($offset);
        }
        return $builder->get();
    }

    public function saveLineasPrepagoOsiptelForToday($ticket)
    {
        $this->userIdentifier = $this->authService->getUserIdentifier();
        $queries = [];
        $queries[] = ["sql" => "BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.SMS_PREPAGO_OSIPTEL_{$this->userIdentifier}';
        EXCEPTION
        WHEN OTHERS THEN
            IF SQLCODE != -942 THEN RAISE; END IF;
        END;"];
        $queries[] = ["sql" => "CREATE TABLE USRAES.SMS_PREPAGO_OSIPTEL_{$this->userIdentifier}(FECHA DATE,MSISDN VARCHAR2(100))"];
        $queries[] = ["sql" => "BEGIN
            INSERT INTO USRAES.SMS_PREPAGO_OSIPTEL_{$this->userIdentifier}
            select TRUNC(SYSDATE, 'DD') FECHA, MSISDN FROM (
                select
                A.Subscription_Access_Number MSISDN
                from dwa.DW_M_SUBSCRIPTION A
                where 
                A.SUBSCRIPTION_SERVICE_CLASS_DESC='TELEFONIA MOVIL'
                AND A.AGREEMENT_STATUS NOT  IN ('D')
                AND A.AGREEMENT_MODE='PREPAGO'
                and substr(a.SUBSCRIPTION_ACCESS_NUMBER,1,3) = '519'
                and A.ID_CARD_VALUE NOT IN
                (                            
                SELECT ID_CARD_VALUE from (
                select c.*,row_number() over (partition by c.ID_CARD_VALUE order by c.SUBSCRIPTION_ACCESS_NUMBER desc) ran from (
                select * from dwa.DW_M_SUBSCRIPTION
                ) c
                ) where ran = 11 and ID_CARD_TYPE_VALUE in ('RUC')
                )
                GROUP BY A.Subscription_Access_Number
            );
            COMMIT;
        END;"];
        $queries[] = ["sql" => "DECLARE
            V_TICKET VARCHAR2(100) := :p_ticket;
        BEGIN
            DELETE FROM USRAES.SMS_PREPAGO_OSIPTEL
            WHERE FECHA = (SELECT FECHA FROM USRAES.SMS_PREPAGO_OSIPTEL_{$this->userIdentifier} FETCH FIRST '1' ROWS ONLY)
            AND TICKET = V_TICKET;
            COMMIT;

            INSERT INTO USRAES.SMS_PREPAGO_OSIPTEL(FECHA, MSISDN, TICKET)
            SELECT FECHA, MSISDN, V_TICKET FROM USRAES.SMS_PREPAGO_OSIPTEL_{$this->userIdentifier}
            WHERE MSISDN NOT IN (
                SELECT MSISDN FROM USRAES.SMS_POSTPAGO_OSIPTEL WHERE FECHA = TRUNC(SYSDATE, 'DD')
                AND TICKET = V_TICKET
            );
            COMMIT;

            DELETE FROM USRAES.SMS_RESUMEN_LOG
            WHERE FECHA = TRUNC(SYSDATE, 'DD')
            AND TICKET = V_TICKET
            AND TIPO_PLAN = 'PREPAGO'
            AND TIPO_SOLICITUD = 'OSIPTEL';
            COMMIT;

            INSERT INTO USRAES.SMS_RESUMEN_LOG(FECHA, TICKET, TIPO_PLAN, TIPO_SOLICITUD)
            VALUES (TRUNC(SYSDATE, 'DD'), V_TICKET, 'PREPAGO', 'OSIPTEL');
            COMMIT;

            MERGE INTO USRAES.SMS_MTC_OSIPTEL_HIST A
            USING (
                SELECT
                V_TICKET TICKET, 'OSIPTEL' TIPO, TRUNC(SYSDATE, 'DD') FECHA, COUNT(*) AS COUNTER
                FROM USRAES.SMS_PREPAGO_OSIPTEL WHERE FECHA = TRUNC(SYSDATE, 'DD') AND TICKET = V_TICKET
            ) B
            ON (A.TICKET = B.TICKET AND A.TIPO = B.TIPO AND A.FECHA = B.FECHA)
            WHEN MATCHED THEN UPDATE SET
                A.NUM_PRE = B.COUNTER
            WHEN NOT MATCHED THEN INSERT (TICKET, TIPO, FECHA, NUM_PRE)
                VALUES(B.TICKET, B.TIPO, B.FECHA, B.COUNTER);
            COMMIT;
        END;", "params" => ["p_ticket" => $ticket]];
        $this->exec_sql($queries);
    }

    public function countLineasPrepagoOsiptelForToday($ticket): int
    {
        $date = new DateTime();
        $result = DB::table("USRAES.SMS_PREPAGO_OSIPTEL")
        ->selectRaw("count(*) as counter")
        ->where("fecha", $date->format("Y-m-d"))
        ->where("ticket", $ticket)
        ->first();
        return $result->counter;
    }

    public function getTicketProcessed($tipo_plan, $tipo_solicitud)
    {
        return DB::table("USRAES.SMS_RESUMEN_LOG")
        ->select("fecha", "ticket")
        ->where("tipo_plan", $tipo_plan)
        ->where("tipo_solicitud", $tipo_solicitud)
        ->get();
    }

    public function saveLog($fecha, $ticket, $tipo_plan, $tipo_solicitud, $file, $fecha_carga)
    {
        DB::table("USRAES.SMS_MTC_OSIPTEL_LOG")
        ->insert([
            "fecha" => $fecha,
            "ticket" => $ticket,
            "tipo_plan" => $tipo_plan,
            "tipo_solicitud" => $tipo_solicitud,
            "log" => $file,
            "fecha_carga" => $fecha_carga,
        ]);
    }

    public function saveDataLog($fecha, $ticket, $tipo_plan, $tipo_solicitud, $file)
    {
        $this->userIdentifier = $this->authService->getUserIdentifier();
        $response = Http::withHeaders([
            "x-user-identifier" => $this->userIdentifier,
        ])
        ->timeout(-1)
        ->post(env("APP_API")."/lineas-mtc-osiptel/logs", [
            "fecha" => $fecha,
            "ticket" => $ticket,
            "tipo_plan" => $tipo_plan,
            "tipo_solicitud" => $tipo_solicitud,
            "localfile" => $file
        ]);
        if(!$response->ok()){
            $message = $response->body();
            if(is_array($message)){
                $message = json_encode($message);
            }
            throw new Exception($message);
        }
        return;

        $queries = [];
        $queries[] = ["sql" => "BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.SMS_PCM_OSIPTEL_LOG_TMP_{$this->userIdentifier}';
        EXCEPTION
        WHEN OTHERS THEN
            IF SQLCODE != -942 THEN RAISE; END IF;
        END;"];
        $queries[] = ["sql" => "CREATE TABLE USRAES.SMS_PCM_OSIPTEL_LOG_TMP_{$this->userIdentifier}(
        hora_envio date,
        hora_entrega date,
        msisdn varchar2(100),
        id_correo varchar2(100),
        tipo_tarea varchar2(100),
        id_transaccion varchar2(250),
        id_recurrencia varchar2(250),
        mensaje varchar2(4000),
        test_campaing_Id varchar2(100),
        id_de_campania varchar2(100),
        tipo_canal varchar2(100),
        tipo_interfaz varchar2(100),
        estado varchar2(100),
        direccion_remitente varchar2(100),
        categoria_campania varchar2(100),
        descripcion_situacion varchar2(100)
        )"];
        $this->exec_sql($queries);

        foreach ($data as $row) {
            DB::table("USRAES.SMS_PCM_OSIPTEL_LOG_TMP_{$this->userIdentifier}")
            ->insert([
                "hora_envio" => $row[0],
                "hora_entrega" => $row[1],
                "msisdn" => $row[2],
                "id_correo" => $row[3],
                "tipo_tarea" => $row[4],
                "id_transaccion" => $row[5],
                "id_recurrencia" => $row[6],
                "mensaje" => $row[7],
                "test_campaing_Id" => $row[8],
                "id_de_campania" => $row[9],
                "tipo_canal" => $row[10],
                "tipo_interfaz" => $row[11],
                "estado" => $row[12],
                "direccion_remitente" => $row[13],
                "categoria_campania" => $row[14],
                "descripcion_situacion" => $row[15],
            ]);
        }

        $params = [
            "p_ticket" => $ticket,
            "p_tipo_plan" => $tipo_plan,
            "p_tipo_solicitud" => $tipo_solicitud,
        ];
        $result = DB::select(DB::raw("SELECT COUNT(1) as counter
        FROM USRAES.SMS_PCM_OSIPTEL_LOG
        WHERE MSISDN IN (SELECT MSISDN FROM USRAES.SMS_PCM_OSIPTEL_LOG_TMP_{$this->userIdentifier}) AND
        carta= :p_ticket AND tipo_solicitud = :p_tipo_solicitud AND tipo_plan = :p_tipo_plan"), $params);
        
        if($result[0]->counter > 0){
            throw new Exception("EL LOG QUE SE ESTA CARGANDO YA TIENE LINEAS CARGADAS, FAVOR DE VALIDAR QUE SEA EL FILE CORRECTO.");
        }

        $queries = [];
        $queries[] = ["sql" => "BEGIN
            INSERT INTO USRAES.SMS_PCM_OSIPTEL_LOG(
            carta, fecha, tipo_solicitud, tipo_plan,
            hora_envio, hora_entrega, msisdn, id_correo, tipo_tarea,
            id_transaccion, id_recurrencia, mensaje, test_campaing_Id, id_de_campania, tipo_canal,
            tipo_interfaz, estado, direccion_remitente, categoria_campania, descripcion_situacion)
            SELECT
            :p_ticket carta,
            to_date(:p_fecha, 'yyyy-mm-dd') fecha,
            :p_tipo_solicitud tipo_solicitud,
            :p_tipo_plan tipo_plan,
            hora_envio, hora_entrega, msisdn, id_correo, tipo_tarea,
            id_transaccion, id_recurrencia, mensaje, test_campaing_Id, id_de_campania, tipo_canal,
            tipo_interfaz, estado, direccion_remitente, categoria_campania, descripcion_situacion
            FROM USRAES.SMS_PCM_OSIPTEL_LOG_TMP_{$this->userIdentifier};
            COMMIT;
        END;", "params" => [
            "p_ticket" => $ticket,
            "p_fecha" => $fecha,
            "p_tipo_plan" => $tipo_plan,
            "p_tipo_solicitud" => $tipo_solicitud,
        ]];
        $this->exec_sql($queries);
    }

    public function findReportBy($ticket, $tipo, DateTime $fecha)
    {
        return DB::table("USRAES.SMS_MTC_OSIPTEL_HIST")
        ->where("ticket", $ticket)
        ->where("tipo", $tipo)
        ->where("fecha", $fecha->format("Y-m-d")." 00:00:00")
        ->first();
    }

    public function deleteBy($ticket, $tipo, DateTime $fecha)
    {
        if($tipo === "PCM"){
            DB::table("USRAES.SMS_POSTPAGO_MTC")
            ->where("fecha", $fecha->format("Y-m-d")." 00:00:00")
            ->where("ticket", $ticket)
            ->delete();

            DB::table("USRAES.SMS_PREPAGO_MTC")
            ->where("fecha", $fecha->format("Y-m-d")." 00:00:00")
            ->where("ticket", $ticket)
            ->delete();
        }else if ($tipo === "OSIPTEL"){
            DB::table("USRAES.SMS_POSTPAGO_OSIPTEL")
            ->where("fecha", $fecha->format("Y-m-d")." 00:00:00")
            ->where("ticket", $ticket)
            ->delete();

            DB::table("USRAES.SMS_PREPAGO_OSIPTEL")
            ->where("fecha", $fecha->format("Y-m-d")." 00:00:00")
            ->where("ticket", $ticket)
            ->delete();
        }

        DB::table("USRAES.SMS_RESUMEN_LOG")
        ->where("fecha", $fecha->format("Y-m-d")." 00:00:00")
        ->where("ticket", $ticket)
        ->where("tipo_solicitud", $tipo)
        ->delete();

        DB::table("USRAES.SMS_MTC_OSIPTEL_HIST")
        ->where("ticket", $ticket)
        ->where("tipo", $tipo)
        ->where("fecha", $fecha->format("Y-m-d")." 00:00:00")
        ->delete();
    }

    //OSIPTEL MSISDN

    public function countLineasOsiptelMsisdn($ticket,$tipo_reporte)
    {
        DB::enableQueryLog();
        $result = DB::select("select COUNT(*) as counter from USRAES.SMS_OSIPTEL_V2 where carta ='{$ticket}' and tipo_plan = '{$tipo_reporte}'")[0];
        return $result->counter;
    }

    public function getReporteOsiptelMsisdn($ticket,$tipo_plan, $offset=0, $limit = null)
    {
        $result = DB::select("select Subscription_Access_Number, ID_CARD_VALUE from USRAES.SMS_OSIPTEL_V2 where carta ='{$ticket}' and tipo_plan = '{$tipo_plan}' OFFSET {$offset} ROWS FETCH NEXT {$limit} ROWS ONLY");

        return $result;
    }

    public function saveLineasOsiptelMsisdn($ticket,$tipo_plan)
    {
        $queries = [];

        $queries[] = ["sql" => "BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.SMS_{$tipo_plan}_V2_{$ticket}';
        EXCEPTION
        WHEN OTHERS THEN
            IF SQLCODE != -942 THEN RAISE; END IF;
        END;"];

        $queries[] = ["sql" => "CREATE TABLE USRAES.SMS_{$tipo_plan}_V2_{$ticket} AS
        select Subscription_Access_Number,ID_CARD_VALUE from (
        select  /*+ PARALLEL(15) */ 
        distinct A.Subscription_Access_Number,A.ID_CARD_VALUE,A.SUBSCRIPTION_SOURCE_SYSTEM_DESC,
        row_number() over(PARTITION by A.Subscription_Access_Number order by A.SUBSCRIPTION_SOURCE_SYSTEM_DESC desc ) as flag 
        --select * 
        from dwa.DW_M_SUBSCRIPTION A
        --from  DWA.DW_M_SUBSCRIPTION_HIST PARTITION(P_202306) A
        where A.SUBSCRIPTION_SERVICE_CLASS_DESC='TELEFONIA MOVIL'
        AND A.AGREEMENT_STATUS NOT IN ('D')
        AND A.AGREEMENT_MODE='{$tipo_plan}'
        and substr(a.SUBSCRIPTION_ACCESS_NUMBER,1,3) = '519' --and Subscription_Access_Number='51949150623' 
        and ID_CARD_VALUE is not null ) where flag=1"];

        $this->exec_sql($queries);
    }

    public function countLineasOsiptelMsisdnV2($ticket,$tipo_plan){
        $result = DB::table("USRAES.SMS_{$tipo_plan}_V2_{$ticket}")
        ->selectRaw("count(*) as counter")
        ->first();
        return $result->counter;
    }

    public function saveLineasOsiptelMsisdnV2($ticket,$tipo_reporte)
    {
        $queries = [];
        if($tipo_reporte == 'PREPAGO'){
            $queries[] = ["sql" => "BEGIN
                INSERT INTO USRAES.SMS_OSIPTEL_V2 
                SELECT '{$ticket}' CARTA,TO_CHAR(SYSDATE,'yyyy-mm-dd') FECHA,'{$tipo_reporte}' TIPO_PLAN,Subscription_Access_Number,ID_CARD_VALUE 
                FROM USRAES.SMS_{$tipo_reporte}_V2_{$ticket} WHERE Subscription_Access_Number NOT IN 
                (SELECT Subscription_Access_Number FROM USRAES.SMS_POSTPAGO_V2_{$ticket});
                COMMIT;
            END;"];
        }else{
            $queries[] = ["sql" => "BEGIN
                INSERT INTO USRAES.SMS_OSIPTEL_V2 
                SELECT '{$ticket}' CARTA,TO_CHAR(SYSDATE,'yyyy-mm-dd') FECHA,'{$tipo_reporte}' TIPO_PLAN,Subscription_Access_Number,ID_CARD_VALUE 
                FROM USRAES.SMS_{$tipo_reporte}_V2_{$ticket};
                COMMIT;
            END;"];
        }
        
        $this->exec_sql($queries);
    }

    public function saveLineasOsiptelHist($ticket,$tipo_reporte)
    {
        $queries = [];
        $queries[] = ["sql" => "BEGIN
            insert into USRAES.SMS_OSIPTEL_V2_HIST 
            SELECT CARTA,FECHA,TIPO_PLAN,COUNT(1) NUM_POST
            FROM USRAES.SMS_OSIPTEL_V2 WHERE CARTA='{$ticket}' AND 
            TIPO_PLAN='{$tipo_reporte}' group by CARTA,fecha,TIPO_PLAN;
            COMMIT;
        END;"];
        $this->exec_sql($queries);
    }

    
    public function saveLineasOsiptelLog($fecha, $ticket, $tipo_plan, $tipo_solicitud, $file, $fecha_carga)
    {
        DB::table("USRAES.SMS_MTC_OSIPTEL_V2_LOG")
        ->insert([
            "fecha" => $fecha,
            "ticket" => $ticket,
            "tipo_plan" => $tipo_plan,
            "tipo_solicitud" => $tipo_solicitud,
            "log" => $file,
            "fecha_carga" => $fecha_carga,
        ]);
    }

    public function getTicketProcessedMsisdn($tipo_plan)
    {
        return DB::table("USRAES.SMS_OSIPTEL_V2_HIST")
        ->select("carta")
        ->where("tipo_plan", $tipo_plan)
        ->get();

        //return DB::select("select TRIM(carta) as carta from USRAES.SMS_OSIPTEL_V2_HIST where TRIM(tipo_plan) = '{$tipo_plan}'");
    }

    public function findReportMsisdnBy($ticket, $tipo)
    {
        return DB::table("USRAES.SMS_OSIPTEL_V2_HIST")
        ->where("carta", $ticket)
        ->where("tipo_plan", $tipo)
        ->first();
    }

    public function deleteMsisdnBy($ticket, $tipo)
    {
        DB::table("USRAES.SMS_OSIPTEL_V2")
        ->where("carta", $ticket)
        ->where("tipo_plan", $tipo)
        ->delete();

        DB::table("USRAES.SMS_OSIPTEL_V2_HIST")
        ->where("carta", $ticket)
        ->where("tipo_plan", $tipo)
        ->delete();
    }


    private function exec_sql(array $plsql)
    {
        foreach($plsql as $row){
            if(array_key_exists('params', $row)){
                DB::connection($this->connection)->statement(DB::Raw($row['sql']), $row['params']);
            }else{
                DB::connection($this->connection)->statement(DB::Raw($row['sql']));
            }
        }
    }
}
