<?php
	/*
	 * Yandex RSA
	 *
	 * @description PHP implementation of Yandex RSA hash http://api.yandex.ru/fotki/doc/concepts/fimptoken.xml
	 * @author Anthony Ilinykh <anthonyilinykh@gmail.com>
	 * @version 1.0
	 */
	
	function yandex_rsa($public_key, $text)
	{	
		$DATA_ARR = array();

		list($NSTR, $ESTR) = explode('#', $public_key);

		$N = gmp_strval(gmp_init($NSTR, 16));
		$E = gmp_strval(gmp_init($ESTR, 16));

		$STEP_SIZE = strlen($NSTR)/2-1;
		$prev_crypted = array();
		
		for($i=0;$i<$STEP_SIZE;$i++)
			array_push($prev_crypted, false);
		
		$hex_out = "";

		for($i=0; $i<strlen($text); $i++)
			array_push($DATA_ARR, ord(substr($text, $i, 1)));
		
		for($i=0; $i<((count($DATA_ARR)-1)/($STEP_SIZE+1)); $i++)
		{
			$tmp = array_slice($DATA_ARR, $i*$STEP_SIZE, ($i+1)*$STEP_SIZE);
			
			for($j=0;$j<count($tmp);$j++)
				$tmp[$j] = ($tmp[$j] ^ $prev_crypted[$j]);
			
			$tmp = array_reverse($tmp);
			$plain = 0;
			for($x=0;$x<count($tmp);$x++)
			{
				$pow_mult = gmp_mul($tmp[$x], gmp_powm(256, $x, $N));
				$plain = gmp_add($plain, $pow_mult);
			}
			
			$hex_result = gmp_strval(gmp_powm($plain, $E, $N), 16);
			
			$array = array();
			for($j=0;$j<strlen($NSTR)-strlen($hex_result)+1;$j++)
				array_push($array, false);
				
			$hex_result = implode('0', $array).$hex_result;
			
			for($x=0;$x<min(strlen($hex_result), count($prev_crypted)*2);$x+=2)
				$prev_crypted[$x/2] = gmp_intval(substr($hex_result, $x, 2));
			
			if(count($tmp) < 16)
				$hex_out .= '00';	
			
			$hex_out .= strtoupper(dechex(count($tmp))).'00';
			
			$ks = strlen($NSTR)/2;
			
			if($ks < 16)
				$hex_out .= '0';
			
			$hex_out .= strtoupper(dechex($ks)).'00';
			$hex_out .= $hex_result;
		}
		
		return preg_replace('#\n\t\s#', '', base64_encode(hex2bin($hex_out)));
	}
?>