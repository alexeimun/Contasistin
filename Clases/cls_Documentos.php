<?php

    class cls_Documentos
    {
        public $_prueba;
        public $_ExisteCodigo;
        public $_IdCuentaGastos;
        public $_IdCuentaConsumo;
        public $_IdParam;
        public $_ConsecutivoSaldosIniciales;
        public $_ConsecutivoNotaContable;
        private $_DB;

        public function __construct()
        {
            $this->_DB = DataBase::Connection();
        }

        public function get_ExisteCodigo()
        {
            return $this->_ExisteCodigo;
        }

        public function set_ExisteCodigo($_ExisteCodigo)
        {
            $this->_ExisteCodigo = $_ExisteCodigo;
        }

        public function EliminaProductosFinal($idUsuario, $idEmpresa)
        {
            $query = "DELETE FROM t_factura_temporal WHERE t_factura_temporal.ID_USUARIO=" . $idUsuario . " AND t_factura_temporal.ID_EMPRESA=" . $idEmpresa;

            return $this->_DB->Exec($query) > 0;
        }

        public function EliminaPagosFinal($idUsuario)
        {
            return $this->_DB->Exec("DELETE FROM t_pagos_t WHERE ID_USUARIO= $idUsuario") > 0;
        }

        public function AnulaFactura($Consecutivo, $idUsuario, $idEmpresa, $Tipodoc)
        {
            $query = "UPDATE `t_movimiento` SET `ANULADO`=1, `USR_ANULA`=" . $idUsuario . ", `FECHA_ANULA`=now() WHERE (`CONSECUTIVO`=" . $Consecutivo . " AND ID_EMPRESA=" . $idEmpresa . "
            AND TIPO_DOC='" . $Tipodoc . "')";

            return $this->_DB->Exec($query) > 0;
        }

        public function ActualizaConsecutivo($Consecutivo, $idEmpresa, $Tipointerno)
        {
            $query = "UPDATE `t_documentos` SET `CONSECUTIVO`= $Consecutivo  WHERE (ID_EMPRESA= $idEmpresa  AND TIPO_INTERNO='" . $Tipointerno . "')";

            return $this->_DB->Exec($query) > 0;
        }

        public function ActualizaReciboAbono($Consecutivo, $idEmpresa, $Abonado)
        {
            $query = "UPDATE t_movimiento SET `ABONADO`= $Abonado
            WHERE  DESCRIPCION='TOTAL' AND  TIPO_DOC='R' AND  ID_EMPRESA=  $idEmpresa  AND DOC_CRUCE= $Consecutivo";

            return $this->_DB->Exec($query) > 0;
        }

        public function ActualizaEgresosAbono($Consecutivo, $idEmpresa, $Abonado)
        {
            $query = "UPDATE t_movimiento SET `ABONADO`= $Abonado
            WHERE  DESCRIPCION='TOTAL' AND  TIPO_DOC='E' AND  ID_EMPRESA= $idEmpresa  AND CONSECUTIVO= $Consecutivo";

            return $this->_DB->Exec($query) > 0;
        }

        public function TraeProductosFinal($idUsuario, $idEmpresa)
        {
            $query = "SELECT
		t_factura_temporal.ID,
		t_factura_temporal.ID_PRODUCTO,
		t_factura_temporal.CANTIDAD,
		t_factura_temporal.DESCUENTO,
		t_factura_temporal.ID_USUARIO,
		t_factura_temporal.ID_EMPRESA,
		t_productos.DESCRIPCION,
		t_productos.CODIGO,
		t_productos.PRECIO,
		t_grupos.CTA_INVENTARIO,
		t_grupos.CTA_VENTAS,
		t_grupos.CTA_COSTO
		FROM
		t_factura_temporal
		INNER JOIN t_productos ON t_factura_temporal.ID_PRODUCTO = t_productos.ID_PRODUCTO
		INNER JOIN t_grupos ON t_productos.ID_GRUPO = t_grupos.ID_GRUPO
		WHERE t_factura_temporal.ID_USUARIO=" . $idUsuario . " AND t_factura_temporal.ID_EMPRESA=" . $idEmpresa . " ORDER BY t_factura_temporal.ID";

            $resulset = $this->_DB->Query($query);
            return $resulset->fetchAll();
        }

        public function TraeInformacionFactura($Consecutivo, $idEmpresa)
        {
            $query = "SELECT DISTINCT
		t_terceros.NOMBRE1,
		t_terceros.NOMBRE2,
		t_terceros.APELLIDO1,
		t_terceros.APELLIDO2,
		t_terceros.NUM_DOCUMENTO,
		t_terceros.DIRECCION,
		t_terceros.TELEFONO,
		t_empresas.NOMBRE,
		t_ciudades.NOMBRE AS CIUDAD,
		t_empresas.LOGO,
		t_empresas.NIT,
		t_empresas.DIRECCION AS DIR_EMPRESA,
		t_empresas.TELEFONO AS TEL_EMPRESA,
		t_credenciales.EMAIL,
		t_movimiento.FECHA_REGISTRO,
		t_movimiento.OBS,
		if(t_movimiento.TIPO_PAGO='CR','Crédito','Contado') AS TIPO_PAGO,
		t_movimiento.ABONADO,
		t_movimiento.TRANSPORTADOR,
		t_movimiento.ANULADO,
		t_usuarios.NOMBRE AS NOMBRE_USUARIO,
		(SELECT LEYENDA FROM t_documentos WHERE TIPO_INTERNO='FACTURA' AND ID_EMPRESA= $idEmpresa ) AS LEYENDA
		FROM
		t_movimiento
		INNER JOIN t_empresas ON t_movimiento.ID_EMPRESA = t_empresas.ID_EMPRESA
		INNER JOIN t_ciudades ON t_movimiento.ID_CIUDAD = t_ciudades.ID_CIUDAD
		INNER JOIN t_credenciales ON t_credenciales.ID_CREDENCIAL=t_empresas.ID_CREDENCIAL
		INNER JOIN t_terceros ON t_movimiento.ID_TERCERO = t_terceros.ID_TERCERO
		INNER JOIN t_usuarios ON t_movimiento.USR_REGISTRO = t_usuarios.ID_USUARIO
		
		WHERE t_movimiento.TIPO_DOC='F' AND t_movimiento.DESCRIPCION='TOTAL' AND t_movimiento.CONSECUTIVO= $Consecutivo  AND t_movimiento.ID_EMPRESA = $idEmpresa";

            $resulset = $this->_DB->Query($query);
            return $resulset->fetchAll();
        }

        public function TraeInformacionEmpresa($idEmpresa)
        {
            $query = "SELECT DISTINCT
		t_empresas.NOMBRE,
		t_empresas.LOGO,
		t_empresas.NIT,
		t_empresas.DIRECCION AS DIR_EMPRESA,
		t_empresas.TELEFONO AS TEL_EMPRESA
		FROM
		t_empresas	
		WHERE t_empresas.ID_EMPRESA = $idEmpresa";

            $resulset = $this->_DB->Query($query);
            return $resulset->fetchAll();
        }


        public function TraeAbonos($Desde, $Hasta, $idEmpresa)
        {
            $query = "SELECT
            Sum(t_movimiento.VALOR) AS TOTAL
            FROM
            t_movimiento
            WHERE t_movimiento.FECHA_REGISTRO BETWEEN '" . $Desde . "' AND '" . $Hasta . "'
            AND t_movimiento.ID_EMPRESA = " . $idEmpresa . " AND t_movimiento.DESCRIPCION LIKE '%ABONO%'";

            $resulset = $this->_DB->Query($query);
            return $resulset->fetchAll();
        }

        public function TraeFormasPagos($Desde, $Hasta, $idEmpresa)
        {
            $query = "SELECT
            t_cuentas.CODIGO AS CUENTA,
            Sum(t_movimiento.VALOR) AS TOTAL,
            UPPER(t_formas_pago.NOMBRE_F_PAGO) AS NOMBRE
            FROM
            t_movimiento
            INNER JOIN t_cuentas ON t_movimiento.ID_CUENTA_MOV = t_cuentas.ID_CUENTA
            INNER JOIN t_formas_pago ON t_movimiento.ID_F_PAGO = t_formas_pago.ID_F_PAGO
            WHERE t_movimiento.ID_F_PAGO <>0
            AND t_movimiento.FECHA_REGISTRO BETWEEN '" . $Desde . "' AND '" . $Hasta . "'
           AND t_movimiento.ID_EMPRESA = " . $idEmpresa . " GROUP BY t_movimiento.ID_F_PAGO";

            $resulset = $this->_DB->Query($query);
            return $resulset->fetchAll();
        }


        public function TraeFacturacionContado($Desde, $Hasta, $idEmpresa)
        {
            $query = "SELECT
SUM(t_movimiento.VALOR)-SUM(t_movimiento.DESCUENTO) AS TOTAL,
t_grupos.NOMBRE AS GRUPO,
t_cuentas.CODIGO AS CUENTA
FROM
t_movimiento
INNER JOIN t_productos ON t_movimiento.ID_PRODUCTO = t_productos.ID_PRODUCTO
INNER JOIN t_grupos ON t_productos.ID_GRUPO = t_grupos.ID_GRUPO
INNER JOIN t_cuentas ON t_movimiento.ID_CUENTA_MOV = t_cuentas.ID_CUENTA
WHERE t_movimiento.ID_PRODUCTO IS NOT NULL AND t_movimiento.ID_PRODUCTO <> 0
AND TIPO_DOC ='F' AND t_movimiento.FECHA_REGISTRO BETWEEN '" . $Desde . "' AND '" . $Hasta . "' AND t_movimiento.TIPO_PAGO='CO'
AND t_movimiento.ID_EMPRESA = " . $idEmpresa . " GROUP BY t_grupos.ID_GRUPO";

            $resulset = $this->_DB->Query($query);
            return $resulset->fetchAll();
        }


        public function TraeCuentasMovidas($Desde, $Hasta, $idEmpresa)
        {
            $query = "SELECT DISTINCT
t_movimiento.ID_CUENTA_MOV,
t_cuentas.CODIGO,
t_cuentas.NOMBRE,
SUM(t_movimiento.VALOR) AS V
FROM
t_movimiento
INNER JOIN t_cuentas ON t_movimiento.ID_CUENTA_MOV = t_cuentas.ID_CUENTA
WHERE t_movimiento.FECHA_REGISTRO BETWEEN '" . $Desde . "' AND '" . $Hasta . "'
AND t_movimiento.ID_EMPRESA = " . $idEmpresa . "
GROUP BY t_movimiento.ID_CUENTA_MOV, t_cuentas.CODIGO, t_cuentas.NOMBRE";

            $resulset = $this->_DB->Query($query);
            return $resulset->fetchAll();
        }


        public function TraeCreditosyDebitosParamCuenta($Desde, $Hasta, $idEmpresa, $idParam, $idCuenta)
        {
            $query = "SELECT (SELECT
SUM(t_movimiento.VALOR) AS VALOR
FROM
t_movimiento
WHERE ID_PARAM_DOCUMENTO=" . $idParam . " AND ID_CUENTA_MOV=" . $idCuenta . "
AND t_movimiento.FECHA_REGISTRO BETWEEN '" . $Desde . "' AND '" . $Hasta . "'
AND t_movimiento.ID_EMPRESA = " . $idEmpresa . " AND t_movimiento.TIPO_MOV='D') AS DEBITOS,
(SELECT
SUM(t_movimiento.VALOR) AS VALOR
FROM
t_movimiento
WHERE ID_PARAM_DOCUMENTO=" . $idParam . " AND ID_CUENTA_MOV=" . $idCuenta . "
AND t_movimiento.FECHA_REGISTRO BETWEEN '" . $Desde . "' AND '" . $Hasta . "'
AND t_movimiento.ID_EMPRESA = " . $idEmpresa . " AND t_movimiento.TIPO_MOV='C') AS CREDITOS";

            $resulset = $this->_DB->Query($query);
            return $resulset->fetchAll();
        }

        public function TraeFacturacionCredito($Desde, $Hasta, $idEmpresa)
        {
            $query = "SELECT
SUM(t_movimiento.VALOR)-SUM(t_movimiento.DESCUENTO) AS TOTAL,
t_grupos.NOMBRE AS GRUPO,
t_cuentas.CODIGO AS CUENTA
FROM
t_movimiento
INNER JOIN t_productos ON t_movimiento.ID_PRODUCTO = t_productos.ID_PRODUCTO
INNER JOIN t_grupos ON t_productos.ID_GRUPO = t_grupos.ID_GRUPO
INNER JOIN t_cuentas ON t_movimiento.ID_CUENTA_MOV = t_cuentas.ID_CUENTA
WHERE t_movimiento.ID_PRODUCTO IS NOT NULL AND t_movimiento.ID_PRODUCTO <> 0
AND TIPO_DOC ='F' AND t_movimiento.FECHA_REGISTRO BETWEEN '" . $Desde . "' AND '" . $Hasta . "' AND t_movimiento.TIPO_PAGO='CR'
AND t_movimiento.ID_EMPRESA = " . $idEmpresa . " GROUP BY t_grupos.ID_GRUPO";

            $resulset = $this->_DB->Query($query);
            return $resulset->fetchAll();
        }


        public function TraeFacturasRecibos($idEmpresa)
        {
            $query = "SELECT DISTINCT
        t_terceros.ID_TERCERO,
        t_terceros.NOMBRE1,
        t_terceros.NOMBRE2,
        t_terceros.APELLIDO1,
        t_terceros.APELLIDO2,
        t_movimiento.FECHA_REGISTRO,
        t_movimiento.VALOR,
        t_movimiento.CONSECUTIVO AS CONSECUTIVO_RECIBO,
        t_movimiento.ANULADO,
         t_movimiento.DOC_CRUCE AS CONSECUTIVO_FACTURA,
        IF(t_movimiento.ABONADO IS NULL ,0,t_movimiento.ABONADO) AS  ABONADO

        FROM
        t_movimiento
        INNER JOIN t_terceros ON t_movimiento.ID_TERCERO = t_terceros.ID_TERCERO
        INNER JOIN t_empresas ON t_movimiento.ID_EMPRESA = t_empresas.ID_EMPRESA

        WHERE TIPO_DOC='R'  AND t_movimiento.DESCRIPCION='TOTAL' AND t_movimiento.DESCRIPCION!=''  AND t_movimiento.ID_EMPRESA = $idEmpresa";

            $resulset = $this->_DB->Query($query);
            return $resulset->fetchAll();
        }

        public function TraeRecibos($idEmpresa)
        {
            $query = "SELECT DISTINCT
        t_terceros.ID_TERCERO,
        t_terceros.NOMBRE1,
        t_terceros.NOMBRE2,
        t_terceros.APELLIDO1,
        t_terceros.APELLIDO2,
        t_movimiento.FECHA_REGISTRO,
        t_movimiento.VALOR,
        t_movimiento.CONSECUTIVO AS CONSECUTIVO_RECIBO,
        t_movimiento.ANULADO,
         t_movimiento.DOC_CRUCE AS CONSECUTIVO_FACTURA,
        IF(t_movimiento.ABONADO IS NULL ,0,t_movimiento.ABONADO) AS  ABONADO

        FROM
        t_movimiento
        INNER JOIN t_terceros ON t_movimiento.ID_TERCERO = t_terceros.ID_TERCERO
        INNER JOIN t_empresas ON t_movimiento.ID_EMPRESA = t_empresas.ID_EMPRESA

        WHERE TIPO_DOC='R'  AND t_movimiento.DESCRIPCION!='TOTAL' AND t_movimiento.DESCRIPCION!=''  AND t_movimiento.ID_EMPRESA = $idEmpresa";

            $resulset = $this->_DB->Query($query);
            return $resulset->fetchAll();
        }

        public function TraeAntecedenteRecibos($Consecutivo, $idEmpresa)
        {
            $query = "SELECT DISTINCT
            t_terceros.ID_TERCERO,
            t_terceros.NOMBRE1,
            t_terceros.NOMBRE2,
            t_terceros.APELLIDO1,
            t_terceros.APELLIDO2,
            t_movimiento.FECHA_REGISTRO,
            case when t_movimiento.VALOR =(SELECT VALOR FROM t_movimiento WHERE DOC_CRUCE=" . $Consecutivo . " limit 0,1) then 0 else t_movimiento.VALOR end as VALOR,
            t_movimiento.CONSECUTIVO AS CONSECUTIVO_RECIBO,
             t_movimiento.DOC_CRUCE AS CONSECUTIVO_FACTURA,
            t_movimiento.ANULADO,
            IF(t_movimiento.ABONADO IS NULL ,0,t_movimiento.ABONADO) AS  ABONADO

        FROM
        t_movimiento
        INNER JOIN t_terceros ON t_movimiento.ID_TERCERO = t_terceros.ID_TERCERO
        INNER JOIN t_empresas ON t_movimiento.ID_EMPRESA = t_empresas.ID_EMPRESA

        WHERE TIPO_DOC='R'  AND DESCRIPCION<>'' and CONSECUTIVO<>0  AND t_movimiento.ID_EMPRESA = $idEmpresa  AND t_movimiento.DOC_CRUCE= $Consecutivo";

            $resulset = $this->_DB->Query($query);
            return $resulset->fetchAll();
        }

        public function TraeAntecedenteEgresos($Consecutivo, $idEmpresa)
        {

            $query = "SELECT DISTINCT
            t_terceros.ID_TERCERO,
            t_terceros.NOMBRE1,
            t_terceros.NOMBRE2,
            t_terceros.APELLIDO1,
            t_terceros.APELLIDO2,
            t_movimiento.FECHA_REGISTRO,
            t_movimiento.VALOR,
            t_movimiento.CONSECUTIVO AS CONSECUTIVO_EGRESOS,
             t_movimiento.DOC_CRUCE AS CONSECUTIVO_GASTOS,
            t_movimiento.ANULADO,
            IF(t_movimiento.ABONADO IS NULL ,0,t_movimiento.ABONADO) AS  ABONADO

        FROM
        t_movimiento
        INNER JOIN t_terceros ON t_movimiento.ID_TERCERO = t_terceros.ID_TERCERO
        INNER JOIN t_empresas ON t_movimiento.ID_EMPRESA = t_empresas.ID_EMPRESA

        WHERE TIPO_DOC='E'  AND DESCRIPCION<>'TOTAL'  AND t_movimiento.ID_EMPRESA = $idEmpresa  AND t_movimiento.DOC_CRUCE= $Consecutivo";

            $resulset = $this->_DB->Query($query);
            return $resulset->fetchAll();
        }


        public function TraeFacturasReimpresion($idEmpresa)
        {
            $query = "SELECT DISTINCT
        t_terceros.ID_TERCERO,
        t_terceros.NOMBRE1,
        t_terceros.NOMBRE2,
        t_terceros.APELLIDO1,
        t_terceros.APELLIDO2,
        t_movimiento.FECHA_REGISTRO,
        t_movimiento.VALOR,
        t_movimiento.CONSECUTIVO,
        t_movimiento.ANULADO,
         if(t_movimiento.TIPO_PAGO='CR','CREDITO','CONTADO') AS TIPO_PAGO,
        IF(t_movimiento.ABONADO IS NULL ,0,t_movimiento.ABONADO) AS  ABONADO

        FROM
        t_movimiento
        INNER JOIN t_terceros ON t_movimiento.ID_TERCERO = t_terceros.ID_TERCERO
        INNER JOIN t_empresas ON t_movimiento.ID_EMPRESA = t_empresas.ID_EMPRESA
        WHERE TIPO_DOC='F' AND t_movimiento.DESCRIPCION='TOTAL'  AND t_movimiento.ID_EMPRESA = $idEmpresa
        AND t_movimiento.ID_CUENTA_MOV=0";

            $resulset = $this->_DB->Query($query);
            return $resulset->fetchAll();
        }

        public function TraeCajaMenorReimpresion($idEmpresa)
        {
            $query = "SELECT DISTINCT
		t_movimiento.FECHA_REGISTRO,
		t_movimiento.VALOR,
		t_movimiento.CONSECUTIVO,
		t_movimiento.ANULADO,
		t_movimiento.OBS,
		t_movimiento.FECHA_REGISTRO,
		t_cuentas.CODIGO,
		t_ciudades.NOMBRE AS 'NOMBRE_CIUDAD',
       concat(t_terceros.NOMBRE1,' ',t_terceros.NOMBRE2,' ',t_terceros.APELLIDO1,' ',t_terceros.APELLIDO2)AS N_COMPLETO

		FROM
		t_movimiento
		 INNER JOIN t_ciudades ON t_ciudades.ID_CIUDAD= t_movimiento.ID_CIUDAD
		 INNER JOIN t_terceros ON t_movimiento.ID_TERCERO = t_terceros.ID_TERCERO
		 INNER JOIN t_conceptos ON t_conceptos.ID_CONCEPTO = t_movimiento.ID_CONCEPTO
		 INNER JOIN t_cuentas ON t_conceptos.ID_CUENTA = t_cuentas.ID_CUENTA

		WHERE TIPO_DOC='C' AND t_movimiento.ID_EMPRESA =$idEmpresa";

            $resulset = $this->_DB->Query($query);
            return $resulset->fetchAll();
        }

        public function TraeEgresosReimpresion($idEmpresa)
        {
            $query = "SELECT DISTINCT
            t_movimiento.CONSECUTIVO,
            t_movimiento.FECHA_REGISTRO,
            t_movimiento.VALOR,
            t_movimiento.OBS,
            t_movimiento.NUMERO,
            t_movimiento.ANULADO,
            t_entidades.NOMBRE_ENTIDAD,
            t_usuarios.NOMBRE as NOMBRE_USR

            FROM
            t_movimiento

        INNER JOIN t_usuarios on t_usuarios.ID_USUARIO=t_movimiento.USR_REGISTRO
        INNER JOIN t_entidades on t_entidades.ID_ENTIDAD=t_movimiento.ID_ENTIDAD
		WHERE TIPO_DOC='G' AND t_movimiento.DESCRIPCION='TOTAL' AND t_movimiento.ID_EMPRESA = $idEmpresa";

            $resulset = $this->_DB->Query($query);
            return $resulset->fetchAll();
        }


        public function TraeDetalleFactura($Consecutivo, $idEmpresa)
        {
            $query = "SELECT DISTINCT
            t_movimiento.DESCRIPCION,
            t_movimiento.CANTIDAD,
            t_movimiento.VALOR,
            t_movimiento.DESCUENTO

            FROM
            t_movimiento
            WHERE TIPO_DOC='F' AND  t_movimiento.ID_PRODUCTO <> 0 AND t_movimiento.ID_CUENTA_MOV<>0 AND t_movimiento.DOC_CRUCE=0
            AND t_movimiento.DESCRIPCION <> 'TOTAL'
            AND t_movimiento.CONSECUTIVO=$Consecutivo  AND t_movimiento.ID_EMPRESA = $idEmpresa";

            $resulset = $this->_DB->Query($query);
            return $resulset->fetchAll();
        }
//IF(t_movimiento.ABONADO IS NULL ,0,t_movimiento.ABONADO) AS  ABONADO 
//" . ($Tipo == 'ok' ? "=" : "<>") . "
        public function TraeDetalleRecibo($Consecutivo, $idEmpresa, $Tipo)
        {
            $query = "SELECT DISTINCT
            t_movimiento.DESCRIPCION,
            t_movimiento.CANTIDAD,
            t_movimiento.VALOR,
            t_movimiento.DESCUENTO
            
            FROM
            t_movimiento
            WHERE TIPO_DOC='R' AND DESCRIPCION <>'' AND t_movimiento.CONSECUTIVO=" . $Consecutivo . " AND t_movimiento.ID_EMPRESA =" . $idEmpresa;

            $resulset = $this->_DB->Query($query);
            return $resulset->fetchAll();
        }

        public function TraeDetalleCM($Consecutivo, $idEmpresa)
        {
            $query = "SELECT
		  concat(t_terceros.NOMBRE1,' ',t_terceros.NOMBRE2,' ',t_terceros.APELLIDO1,' ',t_terceros.APELLIDO2)AS N_COMPLETO,
            t_movimiento.VALOR,
            t_movimiento.OBS,
            t_ciudades.NOMBRE AS 'NOMBRE_CIUDAD',
            t_movimiento.FECHA_REGISTRO,
            t_cuentas.CODIGO,
            t_movimiento.ANULADO,
            t_movimiento.CONSECUTIVO,
           IF (t_conceptos.CONCEPTO= 1,'Ingresos','Gastos') AS CONCEPTO

             FROM t_movimiento
             INNER JOIN t_ciudades ON t_ciudades.ID_CIUDAD= t_movimiento.ID_CIUDAD
             INNER JOIN t_terceros ON t_movimiento.ID_TERCERO = t_terceros.ID_TERCERO
             INNER JOIN t_conceptos ON t_conceptos.ID_CONCEPTO = t_movimiento.ID_CONCEPTO
             INNER JOIN t_cuentas ON t_conceptos.ID_CUENTA = t_cuentas.ID_CUENTA

             WHERE TIPO_DOC='C' AND t_movimiento.ID_EMPRESA = $idEmpresa AND CONSECUTIVO=$Consecutivo";

            $resulset = $this->_DB->Query($query);
            return $resulset->fetchAll();
        }

        public function TraeDetalleGastos($Consecutivo, $idEmpresa)
        {
            $query = "SELECT
               t_movimiento.ABONADO,
                t_movimiento.FECHA_REGISTRO,
                t_movimiento.ANULADO,
                t_movimiento.VALOR,
                t_movimiento.CONSECUTIVO,
                t_movimiento.NUMERO,
                t_entidades.NOMBRE_ENTIDAD

                 FROM t_movimiento
                 INNER JOIN t_entidades on t_entidades.ID_ENTIDAD=t_movimiento.ID_ENTIDAD
                 WHERE TIPO_DOC='G' AND t_movimiento.DESCRIPCION='TOTAL' AND  t_movimiento.ID_EMPRESA = $idEmpresa   AND t_movimiento.CONSECUTIVO= $Consecutivo";

            $resulset = $this->_DB->Query($query);
            return $resulset->fetchAll();
        }

        public function TraeGastosSubtotales($Consecutivo, $idEmpresa)
        {
            $query = "SELECT
           t_movimiento.OBS,
           t_movimiento.VALOR,
            t_cuentas.CODIGO

		 FROM t_movimiento
		 INNER JOIN t_conceptos ON t_conceptos.ID_CONCEPTO = t_movimiento.ID_CONCEPTO
         INNER JOIN t_cuentas ON t_conceptos.ID_CUENTA = t_cuentas.ID_CUENTA

		 WHERE TIPO_DOC='G' AND t_movimiento.DESCRIPCION='SUBTOTAL' AND  t_movimiento.ID_EMPRESA = $idEmpresa   AND CONSECUTIVO= $Consecutivo";

            $resulset = $this->_DB->Query($query);
            return $resulset->fetchAll();
        }

        public function InsertaMovimiento($IdTercero, $IdProducto, $IdCuentaMov, $TipoDoc, $Consecutivo, $IdFormaPago, $Secuencia, $Descripcion, $TipoMov
            , $Cantidad, $Valor, $Descuento, $Obs, $UsrReg, $IdEmpresa, $Fecha, $Tipo = '', $IdConcepto = 0, $DocCruce = 0, $Tipopago = '', $IdEntidad = 0, $Numero = '',
                                          $IdCiudad = 0, $TotalPagos = 0, $Transportador = '', $TipoDocCruce = '')
        {
            $sub = $IdCuentaMov;
            if ($Tipo == 'BN' || $Tipo == 'SV') $sub = "(SELECT ID_CUENTA FROM t_conceptos WHERE ID_CONCEPTO=" . $IdCuentaMov . ")";
            else if ($Tipo == 'Pa') $sub = "(SELECT ID_CUENTA FROM t_formas_pago WHERE ID_F_PAGO=" . $IdFormaPago . ")";

            $query = "INSERT INTO  t_movimiento
       (`ID_TERCERO`, `ID_PRODUCTO`, `ID_CUENTA_MOV`, `TIPO_DOC`, `CONSECUTIVO`, `ID_F_PAGO`, `SECUENCIA`,`DESCRIPCION`, 
       `TIPO_MOV`, `CANTIDAD`, `VALOR`,`DESCUENTO`, `ANULADO`, `OBS`, `USR_REGISTRO`, `ID_EMPRESA`,`FECHA_REGISTRO`,`TIPO`,`ID_CONCEPTO`
       ,`DOC_CRUCE`, `TIPO_PAGO`, `ID_CIUDAD`,`ID_ENTIDAD`,`NUMERO`,`ABONADO`,`TRANSPORTADOR`,`ID_PARAM_DOCUMENTO`,`TIPO_DOC_CRUCE`)
       VALUES
       ( $IdTercero ,  $IdProducto,  $sub,'" . $TipoDoc . "',$Consecutivo, '" . $IdFormaPago . "',  $Secuencia, '" . $Descripcion . "', '" . $TipoMov . "', $Cantidad,  $Valor,$Descuento
       ,0, ' " . $Obs . "',  $UsrReg, $IdEmpresa ,'" . $Fecha . "', '" . $Tipo . "',$IdConcepto, $DocCruce,'" . $Tipopago . "', $IdCiudad, $IdEntidad,' " . $Numero . "', $TotalPagos,'" . $Transportador . "'," . $this->_IdParam . ",'" . $TipoDocCruce . "')";

            return $this->_DB->Exec($query) > 0;
        }


        public function InsertaDocumento($doc, $nit)
        {
            $subquery1 = "(select id_usuario from t_usuarios where documento=" . $doc . ")";
            $subquery2 = "(select id_empresa from t_empresas  where nit=" . $nit . ")";

            $query = "INSERT INTO t_documentos (
		TIPO,NOMBRE_DOCUMENTO,NOMBRE_IMPRESO,CONSECUTIVO,LEYENDA,ESTADO,USR_REGISTRO,
		TIPO_INTERNO,ID_EMPRESA, FECHA_REGISTRO)

	      VALUES('001','FACTURA','FACTURA',1,'LEYENDA',1," . $subquery1 . ",'FACTURA'," . $subquery2 . ",NOW()),
	      ('002','RECIBO','RECIBO',1,'LEYENDA',1," . $subquery1 . ",'RECIBO'," . $subquery2 . ",NOW()),
	      ('003','RECIBO CAJA MENOR','RECIBO CAJA MENOR',1,'LEYENDA',1, " . $subquery1 . ",'RECIBO_CAJA_MENOR'," . $subquery2 . ",NOW()),
	      ('004','GASTOS','GASTOS',1,'LEYENDA',1, " . $subquery1 . ",'GASTOS'," . $subquery2 . ",NOW()),
	      ('005','','',1,'',0, " . $subquery1 . ",'IMPUESTO_CONSUMO'," . $subquery2 . ",NOW()),
	      ('006','','',1,'',0, " . $subquery1 . ",'CXP'," . $subquery2 . ",NOW()),
	      ('007','EGRESOS','EGRESOS',1,'LEYENDA',1, " . $subquery1 . ",'EGRESOS'," . $subquery2 . ",NOW()),
	      ('008','NOTA CONTABLE','NOTA CONTABLE',1,'LEYENDA',1, " . $subquery1 . ",'NOTA_CONTABLE'," . $subquery2 . ",NOW()),
	      ('009','SALDOS INICIALES','SALDOS INICIALES',1,'LEYENDA',1, " . $subquery1 . ",'SALDOS_INICIALES'," . $subquery2 . ",NOW())";

            return $this->_DB->Exec($query) > 0;
        }

        public function TraeInformacionRecibo($Consecutivo, $idEmpresa)
        {
            $query = "SELECT DISTINCT
            t_terceros.NOMBRE1,
            t_terceros.NOMBRE2,
            t_terceros.APELLIDO1,
            t_terceros.APELLIDO2,
            t_terceros.NUM_DOCUMENTO,
            t_terceros.DIRECCION,
            t_terceros.TELEFONO,
            t_empresas.NOMBRE,
            t_empresas.LOGO,
            t_empresas.NIT,
            t_empresas.DIRECCION AS DIR_EMPRESA,
            t_empresas.TELEFONO AS TEL_EMPRESA,
            t_movimiento.FECHA_REGISTRO,
            t_movimiento.OBS,
            t_movimiento.ANULADO,
            t_credenciales.EMAIL,
            IF(t_movimiento.ABONADO IS NULL ,0,t_movimiento.ABONADO) AS  ABONADO,
            t_usuarios.NOMBRE AS NOMBRE_USUARIO,
            (SELECT LEYENDA FROM t_documentos WHERE TIPO_INTERNO='RECIBO' AND ID_EMPRESA=" . $idEmpresa . ") AS LEYENDA
            FROM

            t_movimiento
            INNER JOIN t_empresas ON t_movimiento.ID_EMPRESA = t_empresas.ID_EMPRESA
            INNER JOIN t_credenciales ON t_credenciales.ID_CREDENCIAL=t_empresas.ID_CREDENCIAL
            INNER JOIN t_terceros ON t_movimiento.ID_TERCERO = t_terceros.ID_TERCERO
            INNER JOIN t_usuarios ON t_movimiento.USR_REGISTRO = t_usuarios.ID_USUARIO

            WHERE  t_movimiento.TIPO_DOC='R' AND  t_movimiento.CONSECUTIVO= $Consecutivo AND t_movimiento.ID_EMPRESA = $idEmpresa";

            $resulset = $this->_DB->Query($query);
            return $resulset->fetchAll();
        }

        public function TraePagosFinal($idEmpresa, $Consecutivo, $Tipo, $SegConsecutivo = 0)
        {
            $query = "SELECT
            t_movimiento.VALOR,
            t_formas_pago.NOMBRE_F_PAGO,
            t_entidades.NOMBRE_ENTIDAD,
            t_movimiento.NUMERO

		FROM
		t_movimiento

		INNER JOIN t_entidades ON t_movimiento.ID_ENTIDAD = t_entidades.ID_ENTIDAD
		INNER JOIN t_formas_pago ON t_movimiento.ID_F_PAGO = t_formas_pago.ID_F_PAGO
		WHERE  t_formas_pago.REQUIERE_ENTIDAD=1 AND(t_movimiento.DESCRIPCION='" . $Tipo . ' ' . ($SegConsecutivo == 0 ? $Consecutivo : $SegConsecutivo) . "')
		AND t_movimiento.ID_EMPRESA=" . $idEmpresa . " AND t_movimiento.CONSECUTIVO= $Consecutivo
		UNION
		(SELECT
            t_movimiento.VALOR,
            t_formas_pago.NOMBRE_F_PAGO,
            '' AS NOMBRE_ENTIDAD,
            t_movimiento.NUMERO

		FROM
		t_movimiento

		INNER JOIN t_formas_pago ON t_movimiento.ID_F_PAGO = t_formas_pago.ID_F_PAGO
		WHERE  t_formas_pago.REQUIERE_ENTIDAD=0 AND(t_movimiento.DESCRIPCION='" . $Tipo . ' ' . ($SegConsecutivo == 0 ? $Consecutivo : $SegConsecutivo) . "')
		AND t_movimiento.ID_EMPRESA=" . $idEmpresa . " AND t_movimiento.CONSECUTIVO=" . $Consecutivo . ")";
            $resulset = $this->_DB->Query($query);
            return $resulset->fetchAll();
        }


        public function TraePagosTotalRecibo($idEmpresa, $Consecutivo, $Tipodoc)
        {
            $query = "SELECT
            t_movimiento.VALOR,
            t_formas_pago.NOMBRE_F_PAGO,
            t_entidades.NOMBRE_ENTIDAD,
            t_movimiento.NUMERO

		FROM
		t_movimiento

		INNER JOIN t_entidades ON t_movimiento.ID_ENTIDAD = t_entidades.ID_ENTIDAD
		INNER JOIN t_formas_pago ON t_movimiento.ID_F_PAGO = t_formas_pago.ID_F_PAGO
		WHERE t_formas_pago.REQUIERE_ENTIDAD=1 AND   TIPO_DOC='$Tipodoc' AND t_movimiento.ID_EMPRESA=$idEmpresa
		AND  t_movimiento.CONSECUTIVO= $Consecutivo

                UNION
                SELECT
            t_movimiento.VALOR,
            t_formas_pago.NOMBRE_F_PAGO,
           '' as  NOMBRE_ENTIDAD,
            t_movimiento.NUMERO

		FROM
		t_movimiento

		INNER JOIN t_formas_pago ON t_movimiento.ID_F_PAGO = t_formas_pago.ID_F_PAGO
		WHERE t_formas_pago.REQUIERE_ENTIDAD=0 AND TIPO_DOC='$Tipodoc' AND t_movimiento.ID_EMPRESA=$idEmpresa
		AND t_movimiento.CONSECUTIVO= $Consecutivo";
            $resulset = $this->_DB->Query($query);
            return $resulset->fetchAll();
        }

        public function TraePagosTotal($idEmpresa, $Consecutivo, $Tipodoc)
        {
            $query = "SELECT
            t_movimiento.VALOR,
            t_formas_pago.NOMBRE_F_PAGO,
            t_entidades.NOMBRE_ENTIDAD,
            t_movimiento.NUMERO

		FROM
		t_movimiento

		INNER JOIN t_entidades ON t_movimiento.ID_ENTIDAD = t_entidades.ID_ENTIDAD
		INNER JOIN t_formas_pago ON t_movimiento.ID_F_PAGO = t_formas_pago.ID_F_PAGO
		WHERE t_formas_pago.REQUIERE_ENTIDAD=1 AND   TIPO_DOC='$Tipodoc' AND t_movimiento.ID_EMPRESA=$idEmpresa
		AND  t_movimiento.CONSECUTIVO= $Consecutivo

                UNION
                SELECT
            t_movimiento.VALOR,
            t_formas_pago.NOMBRE_F_PAGO,
           '' as  NOMBRE_ENTIDAD,
            t_movimiento.NUMERO

		FROM
		t_movimiento

		INNER JOIN t_formas_pago ON t_movimiento.ID_F_PAGO = t_formas_pago.ID_F_PAGO
		WHERE t_formas_pago.REQUIERE_ENTIDAD=0 AND TIPO_DOC='$Tipodoc' AND t_movimiento.ID_EMPRESA=$idEmpresa
		AND t_movimiento.CONSECUTIVO= $Consecutivo";
            $resulset = $this->_DB->Query($query);
            return $resulset->fetchAll();
        }

        public function ActualizaRecibo($Consecutivo, $idEmpresa, $Abono)
        {
            return $this->_DB->Exec("UPDATE t_movimiento SET ABONADO=ABONADO + $Abono  WHERE ID_EMPRESA= $idEmpresa AND CONSECUTIVO= $Consecutivo") > 0;
        }

        public function TraeParametrosSaldosIniciales($idEmpresa)
        {
            $query = "SELECT
		 CONSECUTIVO,ID_DOCUMENTO FROM t_documentos
		 WHERE TIPO_INTERNO='SALDOS_INICIALES' AND ID_EMPRESA= $idEmpresa";

            $resulset = $this->_DB->Query($query);
            $Campos = $resulset->fetchAll();

            $this->_ConsecutivoSaldosIniciales = $Campos[0][0];
            $this->_IdParam = $Campos[0][1];
        }

        public function TraeParametrosNotaContable($idEmpresa)
        {
            $query = "SELECT
		 CONSECUTIVO,ID_DOCUMENTO FROM t_documentos
		 WHERE TIPO_INTERNO='NOTA_CONTABLE' AND ID_EMPRESA= $idEmpresa";

            $resulset = $this->_DB->Query($query);
            $Campos = $resulset->fetchAll();

            $this->_ConsecutivoNotaContable = $Campos[0][0];
            $this->_IdParam = $Campos[0][1];

        }

        public function TraeProductosServicios($Ano, $Mes, $Dia, $IdEmpresa)
        {
            $query = "SELECT SUM(t_movimiento.VALOR)

        from t_movimiento

            INNER JOIN t_grupos ON t_movimiento.ID_CUENTA_MOV=t_grupos.CTA_VENTAS
          INNER JOIN t_productos on t_productos.ID_PRODUCTO=t_movimiento.ID_PRODUCTO
          AND t_grupos.ID_GRUPO=t_productos.ID_GRUPO

        WHERE t_movimiento.TIPO='P' AND YEAR(t_movimiento.FECHA_REGISTRO)=$Ano AND MONTH(t_movimiento.FECHA_REGISTRO)=$Mes
        AND DAY(t_movimiento.FECHA_REGISTRO)=$Dia AND t_movimiento.ID_EMPRESA=$IdEmpresa";

            $resulset = $this->_DB->Query($query);
            $Escalar = $resulset->fetchAll();
            return $Escalar[0][0];
        }

        public function TraeCompraBienes($Ano, $Mes, $Dia, $IdEmpresa)
        {
            $query = "SELECT SUM(t_movimiento.VALOR)

             from t_movimiento

             WHERE t_movimiento.TIPO='BN' AND YEAR(t_movimiento.FECHA_REGISTRO)=$Ano AND MONTH(t_movimiento.FECHA_REGISTRO)=$Mes
             AND DAY(t_movimiento.FECHA_REGISTRO)=$Dia AND t_movimiento.ID_EMPRESA=$IdEmpresa";

            $resulset = $this->_DB->Query($query);
            $Escalar = $resulset->fetchAll();
            return $Escalar[0][0];
        }

        public function TraePagoServicios($Ano, $Mes, $Dia, $IdEmpresa)
        {
            $query = "SELECT SUM(t_movimiento.VALOR)

             from t_movimiento

             WHERE t_movimiento.TIPO='SV' AND YEAR(t_movimiento.FECHA_REGISTRO)=$Ano AND MONTH(t_movimiento.FECHA_REGISTRO)=$Mes
             AND DAY(t_movimiento.FECHA_REGISTRO)=$Dia AND t_movimiento.ID_EMPRESA=$IdEmpresa";

            $resulset = $this->_DB->Query($query);
            $Escalar = $resulset->fetchAll();
            return $Escalar[0][0];
        }

        public function TraeIVA($Ano, $Mes, $Dia, $IdEmpresa)
        {
            $query = "SELECT SUM(t_movimiento.VALOR)

             from t_movimiento

                 WHERE (t_movimiento.TIPO='I' OR t_movimiento.TIPO='Com') AND YEAR(t_movimiento.FECHA_REGISTRO)=$Ano AND MONTH(t_movimiento.FECHA_REGISTRO)=$Mes
             AND DAY(t_movimiento.FECHA_REGISTRO)=$Dia AND t_movimiento.ID_EMPRESA=$IdEmpresa";

            $resulset = $this->_DB->Query($query);
            $Escalar = $resulset->fetchAll();
            return $Escalar[0][0];
        }

        public function RequiereTercero($IdCuenta, $IdEmpresa)
        {
            $query = "SELECT MANEJA_TERCERO from t_cuentas where ID_CUENTA=$IdCuenta and ID_EMPRESA=$IdEmpresa";
            $resulset = $this->_DB->Query($query);
            $Escalar = $resulset->fetchAll();
            return $Escalar[0][0];
        }

        public function TraeCiudadTercero($IdTercero)
        {
            $query = "SELECT ID_CIUDAD from t_terceros WHERE ID_TERCERO=$IdTercero";
            $resulset = $this->_DB->Query($query);
            $Escalar = $resulset->fetchAll();
            return $Escalar[0][0];
        }

        public function TraeCuentaConcepto($IdConcepto)
        {
            $resulset = $this->_DB->Query("SELECT ID_CUENTA from t_conceptos WHERE ID_CONCEPTO=$IdConcepto");
            $Escalar = $resulset->fetchAll();
            return $Escalar[0][0];
        }
    }