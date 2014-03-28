<?php
	class formato{
	private $num_letra;
	private $num_tamaño;
	private $num_color;
	private $num_fondo;

	private $tit_letra;
	private $tit_tamaño;
	private $tit_color;
	private $tit_fondo;

	private $alineacion;
	private $borde;
	private $col_ancho;
	private $col_alto;

	function get_num_letra(){
	return $this->num_letra;
	}
	function set_num_letra($valor){
 	    $this->num_letra = $valor;
	}
	function get_num_tamaño(){
	return $this->num_tamaño;
	}
	function set_num_tamaño($valor){
 	    $this->num_tamaño = $valor;
	}
	function get_num_color(){
	return $this->num_color;
	}
	function set_num_color($valor){
 	    $this->num_color = $valor;
	}
	function get_num_fondo(){
	return $this->num_fondo;
	}
	function set_num_fondo($valor){
 	    $this->num_fondo = $valor;
	}
	function get_num_letra(){
	return $this->num_letra;
	}
	function set_tit_letra($valor){
 	    $this->tit_letra = $valor;
	}
	function get_tit_tamaño(){
	return $this->tit_tamaño;
	}
	function set_tit_tamaño($valor){
 	    $this->tit_tamaño = $valor;
	}
	function get_tit_color(){
	return $this->tit_color;
	}
	function set_tit_color($valor){
 	    $this->tit_color = $valor;
	}
	function get_tit_fondo(){
	return $this->tit_fondo;
	}
	function set_tit_fondo($valor){
 	    $this->tit_fondo = $valor;
	}
	function get_alineacion(){
	return $this->alineacion;
	}
	function set_alineacion($valor){
 	    $this->alineacion = $valor;
	}
	function get_borde(){
	return $this->borde;
	}
	function set_borde($valor){
 	    $this->borde = $valor;
	}
	function get_col_ancho(){
	return $this->col_ancho;
	}
	function set_col_ancho($valor){
 	    $this->col_ancho = $valor;
	}
	function get_col_alto(){
	return $this->col_alto;
	}
	function set_col_alto($valor){
 	    $this->col_alto = $valor;
	}
	function setNumerors($letra ="Aruial", $tamaño "2", $color ="#FFFF00", $fondo ="#AA6F00"){
 	    $this->set_num_letra($letra);
		if(is_int($tamaño)){
			 $this->set_num_tamaño(strval($tamaño));
		}else{
			 $this->set_num_tamaño($tamaño);
		}
		
			 $this->set_num_color($color);
			 $this->set_num_fondo($fondo);
	}
function setNumerors($letra ="Aruial", $tamaño "2", $color ="#FFFFFF", $fondo ="#0000FF"){
 		$this->set_tit_letra($letra);
		if(is_int($tamaño)){
			 $this->set_tit_tamaño(strval($tamaño));
		}else{
			 $this->set_tit_tamaño($tamaño);
		}
		
			 $this->set_tit_color($color);
			 $this->set_tit_fondo($fondo);
	}
function setNumerors($alin ="center", $borde = 1, $ancho =35, $alto =30){
 	    $this->set_alineacion($alin);
		$this->set_borde($borde);
		$this->set_ancho($ancho);
		$this->set_alto($alto);
	}
	}

?>
