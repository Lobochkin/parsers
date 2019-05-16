<?php
mysqli_set_charset($connection,"utf8");
if (mysqli_connect_errno()) {
	die('Ошибка соединения: ' . mysqli_connect_error());
}
include 'simple_html_dom.php';

ini_set('memory_limit', '500M');
set_time_limit(0);

$url = "https://razmerkoles.ru";
$zz = 0;
function getCurlResult ($url, $headers = []) {
	while (true) {
			
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); /* ДОБАВЛЯЕМ ЗАГОЛОВКИ */
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, 1); 
		$proxy = mysqli_query($connection,"__function_proxy__()");
		ClearRecordsets();		
		$proxy_arr = mysqli_fetch_array($proxy, MYSQLI_NUM);
		curl_setopt($ch, CURLOPT_PROXY, $proxy_arr[0]);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/64.0.3282.140 Safari/537.36 Edge/17.17134');
		curl_setopt($ch, CURLOPT_VERBOSE, true);
		curl_setopt($ch, CURLOPT_COOKIEJAR, "/var/www/docs/lobochkin.ru/cookies");
		curl_setopt($ch, CURLOPT_COOKIEFILE, "/var/www/docs/lobochkin.ru/cookies");
		$htmltext = curl_exec($ch);
		if (strlen($htmltext) < 10) {
	  	  echo "<h3>Удаляем " . $arrproxy[0] . "--> осталось "  . (count($arrproxy) - 1) . " --> пробуем: " . $arrproxy[1] . "</h3>";
		  $arrproxy = array_slice($arrproxy, 1);
	  	mysqli_query($connection,"__function_proxy_delete__('".$proxy_arr[0]."')");

		} else {return $htmltext; exit;}
	}
	
}

$name_img = 0;

$html = str_get_html(getCurlResult($url));

$htmlArr = $html->find('.main-list-brand span a'); // массив марок

foreach ($htmlArr as $a) {
	$hrefBrand = $a->href;
	$brand = $a->plaintext;

	$urlBrand = $url . $hrefBrand;
	$html1 = str_get_html(getCurlResult($urlBrand));
	$htmlArr1 = $html1->find('.brand-list-others span a');// массив моделей
	foreach ($htmlArr1 as $a1) {
		$hrefModel = $a1->href;
		$model = $a1->plaintext;	

		$urlModel = $url . $hrefModel;
		$html2 = str_get_html(getCurlResult($urlModel));
		$htmlArr2 = $html2->find('.large-tag-view', 0)->find('span a');// массив годов
		foreach ($htmlArr2 as $a2) {
			$hrefYear = $a2->href;
			$year = $a2->plaintext;
			$urlYear = $url . $hrefYear;
			// $urlYear = "https://razmerkoles.ru/size/bmw/5-series/2007/";
			$html3 = str_get_html(getCurlResult($urlYear));	
			$zz++;
		if($zz > 5) {
			exit;
		}
			$htmlArr3 = $html3->find('#vehicle-market-data .vehicle-market'); // массив внутренних рынков

			preg_match('/sRwd=\'(.*?)\'/', $html3, $m); // Достаю значение sRwd=
			$str_url = $m[1];
			$url_code = "https://razmerkoles.ru" . $str_url;
			$headers[] = 'Accept-Language: ru-RU,ru;q=0.8,en-US;q=0.5,en;q=0.3';
			$headers[] = 'Referer: ' . $urlYear;
			$headers[] = 'X-Requested-With: XMLHttpRequest';
			
			$str_curl_code = getCurlResult($url_code, $headers);// получаю json закодированную строку
			$str_code = "";
			 for ($i=0; $i < strlen($str_curl_code); $i++) {			    	
				$str_code .= ctype_upper($str_curl_code[$i]) ? strtolower($str_curl_code[$i]) : strtoupper($str_curl_code[$i]);			    
			}
			$str_code_json = base64_decode($str_code);
			$arr = json_decode($str_code_json, true); // получаю массив характеристик

			if(count($htmlArr3) != 1) {// проверка есть ли разделения на внутренние рынки
				foreach ($htmlArr3 as $key => $div) {
					$market = $html3->find('#vehicle-market-data .vehicle-market h4', $key)->plaintext; // выводит название внутреннего рынка
					
					foreach ($div->find('.modification-item') as $modifications) { // проходит по модификациям модели на одном рынке
						$modification = $modifications->find('h5.same-tag-cloud', 0);
						// $modification = trim($modification);
						$nameModel = $modification->find('span', 0)->innertext;
						$nameEngine = trim($modification->find('span', 1)->innertext);
						$version = "Модификация: " . $nameModel . " Двигатель: " . $nameEngine;
						

						$sizeBoltСipher = $modifications->find('tbody', 0)->attr['data-vehicle'];						
						$result = preg_match_all('/\d{3}/',$sizeBoltСipher,$arrUnicod);
					    $sizeBolt = '';
					    foreach ($arrUnicod[0] as $unicod) {
					    	$sizeBolt .= chr($unicod);
					    }

					    $string_infomation = "";
					    foreach ($modifications->find('.span7 div div') as $value) {					    	
					    	if (!strpos($value->plaintext, 'Двигатель')) {
					    		$string_infomation .= $value->plaintext . ";";
					    	}
					    }					    
					    $infomation_engine = $modifications->find('.span7', 0)->find('[id]', 0)->attr['id'];// достаю значение id двигателя
					    	
					    $infomation_img_arr = $modifications->find('.span7 div div', 0)->find('a');// массив ссылок с атрибутом data-content = src
					    $url1_str = ";";
					    foreach ($infomation_img_arr as $infomation_img) {
					    	$str = $infomation_img->attr['data-content'];					    	
					    	preg_match('/src=\'(.*?)\'/', $str, $s); // Достаю значение src=
					    	$arr_img = explode("/", $s[1]);	
							$name_img = end($arr_img);														
							$str_img = getCurlResult($s[1]); // беру картинку в виде строки
							$url1 = "/var/www/docs/lobochkin.ru/pr25/img/" . $name_img; // записываю адрес картинки на сервере 
							// $file = fopen($url1,"x");
							// fwrite($file, $str_img); // записываю на сервер картинку
							// fclose($file);
							$url1_str .= "http://lobochkin.ru/pr25/img/" . $name_img . ";"; // cтрока с адресами картинок на нашем сервере
					    }
					  

					    $infomation_center_bore = "";
					    $infomation_fixings = "";
					    $infomation_torque = "";
					    $infomation_thread = "";
					    foreach ($modifications->find('.span5 div div') as $div) {
					    	if (strpos($div->plaintext, 'Диаметр')) {
					    		$infomation_center_bore = $arr[$div->find('[id]', 0)->attr['id']];
					    	} elseif (strpos($div->plaintext, 'Тип крепежа')) {
					    		$infomation_fixings = $div->plaintext;
					    	} elseif (strpos($div->plaintext, 'Момент')) {
					    		$infomation_torque = $arr[$div->find('[id]', 0)->attr['id']];
					    	} elseif (strpos($div->plaintext, 'Крепеж')) {
					    		$infomation_thread = $arr[$div->find('[id]', 0)->attr['id']]; 
					    	}
					    }
					    $type_fasteners = "– Диаметр Ц/О: " . $infomation_center_bore . $infomation_fixings . ($infomation_torque?(" -Момент затяжки: " . $infomation_torque):"") . " -Крепеж: " . $infomation_thread;
					  

						foreach ($modifications->find('tbody tr') as $k => $size) {						

							if (is_object($size->find('.data-tire', 0))) {
								$data_pressure = $size->find('.data-pressure .unit-metric-data', 0)->plaintext?:"";
								if (is_object($size->find('.rear-tire-data-full', 0))) {// если передние и задние колеса разных размеров

									$sizeTireFront = $size->find('.data-tire')[0]->attr['data-front'];
									$sizeTireRear = $size->find('.rear-tire-data-full', 0)->plaintext;

									$strCodeFront = $size->find('.data-rim span')[0]->attr['data-rim'];									
									$strRegistrFront = "";
								    for ($i=0; $i < strlen($strCodeFront); $i++) {			    	
								    	$strRegistrFront .= ctype_upper($strCodeFront[$i]) ? strtolower($strCodeFront[$i]) : strtoupper($strCodeFront[$i]);	    
								    } 
								    $sizeDiskFront = base64_decode($strRegistrFront);

								    $strCodeRear = $size->find('.rear-rim-data-full span')[0]->attr['data-rim'];									
									$strRegistrRear = "";
								    for ($i=0; $i < strlen($strCodeRear); $i++) {			    	
								    	$strRegistrRear .= ctype_upper($strCodeRear[$i]) ? strtolower($strCodeRear[$i]) : strtoupper($strCodeRear[$i]);			    
								    } 
								    $sizeDiskRear = base64_decode($strRegistrRear);
								    $stock = strpos($size->outertext, 'stock') ? "*" : "";
								    								 
								    mysqli_query($connection,"__function_data__('".$brand."', '".$model."', '".$year."', '".$market."', '".$version."', '".$url1_str."', '".$string_infomation."', '".$arr[$infomation_engine]."', '".$type_fasteners."', '"."Front: " . $sizeTireFront . " / Rear: " . $sizeTireRear ."', '"."Front: " . $sizeDiskFront . " / Rear: " . $sizeDiskRear."', '".$sizeBolt."', '".$data_pressure."', '".$stock."', '".$urlYear."')");
								    echo $brand . '---' . $model . '---' . $year . '---' . $market . '---' . $version . '---' . $url1_str . '---' . $string_infomation . '---' . $arr[$infomation_engine] . '---' . $type_fasteners . '---' . "Front: " . $sizeTireFront . " / Rear: " . $sizeTireRear . '---' . "Front: " . $sizeDiskFront . " / Rear: " . $sizeDiskRear . '---' . $sizeBolt . '---' . $data_pressure . '---' . $stock . "<br>";
								  
								    
								} else { // если передние и задние колеса одинаковые
									$sizeTire = $size->find('.data-tire')[0]->attr['data-front'];

									$strCode = $size->find('.data-rim span')[0]->attr['data-rim'];									
									$strRegistr = "";
								    for ($i=0; $i < strlen($strCode); $i++) {			    	
								    	$strRegistr .= ctype_upper($strCode[$i]) ? strtolower($strCode[$i]) : strtoupper($strCode[$i]);			    
								    } 
								    $sizeDisk = base64_decode($strRegistr);
								    $stock = strpos($size->outertext, 'stock') ? "*" : "";
								    
									mysqli_query($connection,"__function_data__('".$brand."', '".$model."', '".$year."', '".$market."', '".$version."', '".$url1_str."', '".$string_infomation."', '".$arr[$infomation_engine]."', '".$type_fasteners."', '".$sizeTire."', '".$sizeDisk."', '".$sizeBolt."', '".$data_pressure."', '".$stock."', '".$urlYear."')");

									echo $brand . '---' . $model . '---' . $year . '---' . $market . '---' . $version . '---' . $url1_str . '---' . $string_infomation . '---' . $arr[$infomation_engine] . '---' . $type_fasteners . '---' . $sizeTire . '---' . $sizeDisk . '---' . $sizeBolt . '---' . $data_pressure . '---' . $stock . "<br>";
								}								
							}						
						}
					}					
				}
				
			} else { //если нет внутреннего рынка
				foreach ($html3->find('#vehicle-market-data .vehicle-market .modification-item') as $modifications) { // проходит по модификациям модели на одном рынке
						$modification = $modifications->find('h5.same-tag-cloud', 0);
						$nameModel = $modification->find('span', 0)->innertext;
						$nameEngine = trim($modification->find('span', 1)->innertext);
						$version = "Модификация: " . $nameModel . " Двигатель: " . $nameEngine;
						
						$sizeBoltСipher = $modifications->find('tbody', 0)->attr['data-vehicle'];						
						$result = preg_match_all('/\d{3}/',$sizeBoltСipher,$arrUnicod);
					    $sizeBolt = '';
					    foreach ($arrUnicod[0] as $unicod) {
					    	$sizeBolt .= chr($unicod);
					    }

					    $string_infomation = "";
					    foreach ($modifications->find('.span7 div div') as $value) {
					    	
					    	if (!strpos($value->plaintext, 'Двигатель')) {
					    		$string_infomation .= $value->plaintext . ";";
					    	}
					    }					    
					    $infomation_engine = $modifications->find('.span7', 0)->find('[id]', 0)->attr['id'];// достаю значение id двигателя
					    
					    $infomation_img_arr = $modifications->find('.span7 div div', 0)->find('a');// массив ссылок с атрибутом data-content = src
					    $url1_str = ";";
					    foreach ($infomation_img_arr as $infomation_img) {
					    	$str = $infomation_img->attr['data-content'];
					    	preg_match('/src=\'(.*?)\'/', $str, $s); // Достаю значение src=
							$arr_img = explode("/", $s[1]);	
							$name_img = end($arr_img);							
							$str_img = getCurlResult($s[1]); // беру картинку в виде строки
							$url1 = "/var/www/docs/lobochkin.ru/pr25/img/" . $name_img; // записываю адрес картинки на сервере 
							// $file = fopen($url1,"x");
							// fwrite($file, $str_img); // записываю на сервер картинку
							// fclose($file);
							$url1_str .= "http://lobochkin.ru/pr25/img/" . $name_img . ";"; // cтрока с адресами картинок на нашем сервере
					    }				  
					    $infomation_center_bore = "";
					    $infomation_fixings = "";
					    $infomation_torque = "";
					    $infomation_thread = "";
					   foreach ($modifications->find('.span5 div div') as $div) {
					    	if (strpos($div->plaintext, 'Диаметр')) {
					    		$infomation_center_bore = $arr[$div->find('[id]', 0)->attr['id']];
					    	} elseif (strpos($div->plaintext, 'Тип крепежа')) {
					    		$infomation_fixings = $div->plaintext;
					    	} elseif (strpos($div->plaintext, 'Момент')) {
					    		$infomation_torque = $arr[$div->find('[id]', 0)->attr['id']];
					    	} elseif (strpos($div->plaintext, 'Крепеж')) {
					    		$infomation_thread = $arr[$div->find('[id]', 0)->attr['id']]; 
					    	}
					    }
					    $type_fasteners = "– Диаметр Ц/О: " . $infomation_center_bore . $infomation_fixings . ($infomation_torque?(" -Момент затяжки: " . $infomation_torque):"") . " -Крепеж: " . $infomation_thread;
						
						foreach ($modifications->find('tbody tr') as $k => $size) {	
							
							if (is_object($size->find('.data-tire', 0))) {
								$data_pressure = $size->find('.data-pressure .unit-metric-data', 0)->plaintext?:"";
								if (is_object($size->find('.rear-tire-data-full', 0))) {// если передние и задние колеса разных размеров

									$sizeTireFront = $size->find('.data-tire')[0]->attr['data-front'];
									$sizeTireRear = $size->find('.rear-tire-data-full', 0)->plaintext;

									$strCodeFront = $size->find('.data-rim span')[0]->attr['data-rim'];									
									$strRegistrFront = "";
								    for ($i=0; $i < strlen($strCodeFront); $i++) {			    	
								    	$strRegistrFront .= ctype_upper($strCodeFront[$i]) ? strtolower($strCodeFront[$i]) : strtoupper($strCodeFront[$i]);	    
								    } 
								    $sizeDiskFront = base64_decode($strRegistrFront);

								    $strCodeRear = $size->find('.rear-rim-data-full span')[0]->attr['data-rim'];									
									$strRegistrRear = "";
								    for ($i=0; $i < strlen($strCodeRear); $i++) {			    	
								    	$strRegistrRear .= ctype_upper($strCodeRear[$i]) ? strtolower($strCodeRear[$i]) : strtoupper($strCodeRear[$i]);			    
								    } 
								    $sizeDiskRear = base64_decode($strRegistrRear);
								    $stock = strpos($size->outertext, 'stock') ? "*" : "";
								   
								    mysqli_query($connection,"__function_data__('".$brand."', '".$model."', '".$year."', 'not', '".$version."', '".$url1_str."', '".$string_infomation."', '".$arr[$infomation_engine]."', '".$type_fasteners."', '"."Front: " . $sizeTireFront . " / Rear: " . $sizeTireRear ."', '"."Front: " . $sizeDiskFront . " / Rear: " . $sizeDiskRear."', '".$sizeBolt."', '".$data_pressure."', '".$stock."', '".$urlYear."')");

								    echo $brand . '---' . $model . '---' . $year . '---' . 'not' . '---' . $version . '---' . $url1_str . '---' . $string_infomation . '---' . $arr[$infomation_engine] . '---' . $type_fasteners . '---' . "Front: " . $sizeTireFront . " / Rear: " . $sizeTireRear . '---' . "Front: " . $sizeDiskFront . " / Rear: " . $sizeDiskRear . '---' . $sizeBolt . '---' . $data_pressure . '---' . $stock . "<br>";

								} else { // если передние и задние колеса одинаковые
									$sizeTire = $size->find('.data-tire')[0]->attr['data-front'];

									$strCode = $size->find('.data-rim span')[0]->attr['data-rim'];									
									$strRegistr = "";
								    for ($i=0; $i < strlen($strCode); $i++) {			    	
								    	$strRegistr .= ctype_upper($strCode[$i]) ? strtolower($strCode[$i]) : strtoupper($strCode[$i]);			    
								    } 
								    $sizeDisk = base64_decode($strRegistr);
								    $stock = strpos($size->outertext, 'stock') ? "*" : "";
								    
									mysqli_query($connection,"__function_data__('".$brand."', '".$model."', '".$year."', 'not', '".$version."', '".$url1_str."', '".$string_infomation."', '".$arr[$infomation_engine]."', '".$type_fasteners."', '".$sizeTire."', '".$sizeDisk."', '".$sizeBolt."', '".$data_pressure."', '".$stock."', '".$urlYear."')");
									echo $brand . '---' .  $model . '---' . $year . '---' . 'not'. '---' . $version . '---' . $url1_str . '---' . $string_infomation . '---' . $arr[$infomation_engine] . '---' . $type_fasteners . '---' . $sizeTire . '---' . $sizeDisk . '---' . $sizeBolt . '---' . $data_pressure . '---' . $stock . "<br>";
								}
							}								
						}
					}
			}
			$html3->clear(); // подчищаем за собой
			unset($html3);	
		}
		$html2->clear(); // подчищаем за собой
		unset($html2);
	}
	$html1->clear(); // подчищаем за собой
	unset($html1);
}
$html->clear(); // подчищаем за собой
unset($html);  	
?>
