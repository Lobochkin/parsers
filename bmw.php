<?
include '../simple_html_dom.php';
$url0 = 'https://www.ilcats.ru';
$url = 'https://www.ilcats.ru/bmw/';
ini_set('memory_limit', '500M');
set_time_limit(0);
date_default_timezone_set("Europe/Moscow");
function d($var) 
{
    echo "<pre>";
    var_dump($var);
    echo "</pre>";
}

$server = "******"; /* имя хоста (уточняется у провайдера), если работаем на локальном сервере, то указываем localhost */
$username = "******";  // Имя пользователя БД 
$password = "******"; /* Пароль пользователя, если у пользователя нет пароля то, оставляем пустым */
$database = "******";
$db_table = "******";
header('Content-Type: text/html; charset=utf-8');
$mysqli = new mysqli($server, $username, $password, $database);

if ($mysqli->connect_error) {
    die('Ошибка : ('. $mysqli->connect_errno .') '. $mysqli->connect_error);
} 

function get_curl_result($url, $headers = []) {
	global $mysqli, $db_table;
	while (true) {
		$result = $mysqli->query("SELECT * FROM ".$db_table." ORDER BY RAND() LIMIT 1");
		$row = $result->fetch_assoc();
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); 
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION,1);
		curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, 1); 
		curl_setopt($ch, CURLOPT_PROXY, trim($row['ip']));
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/73.0.3683.103 Safari/537.36');
		curl_setopt($ch, CURLOPT_COOKIEJAR, "cookies.txt");
		curl_setopt($ch, CURLOPT_COOKIEFILE, "cookies.txt");
		curl_setopt($ch, CURLPROTO_HTTPS,1);
		// usleep(500000);
		$htmltext = curl_exec($ch);
		curl_close($ch);		
		if (strlen($htmltext) < 3000 || stripos($htmltext, 'Please enable cookies') !== false) {
			d(strlen($htmltext));
			$result = $mysqli->query("DELETE FROM  ".$db_table." WHERE ip = '".$row['ip']."'");
			$count = $mysqli->query("SELECT COUNT(*) FROM ".$db_table."")->fetch_assoc();
			echo "<div>Удаляем " , trim($row['ip']) , "--> осталось ", (string)$count['COUNT(*)'] ,  " --> пробуем: другой, текущее время " , date("H:i:s") , " </div>";			
		} else {
			return $htmltext; 
			break 1;
		}
	}
}
	
function get_data($object, $attr, $metod, $message = '')
{
	$obj = $object->find($attr, 0);
	if (is_object($obj)) {
		return $obj->$metod;
	}
	return $message;
}
$headers[0] = 'referer: ' . $url0;
$html1 = '';
$html1 = str_get_html(get_curl_result($url,$headers));// страница  рынка
$name_brand = '';
$name_brand = 'bmw'; 

$arr_market = [];
$arr_market = $html1->find('.List .List .name a');
foreach ($arr_market as $market) {
	$name_market = $market->plaintext;
	d($name_market);
	$url_market = $url0 . $market->href;
	$headers[0] = 'referer: ' . $url; 
	$html2 = '';
	$html2 = str_get_html(get_curl_result($url_market, $headers));// страница модели 
	echo '#' , $url_market , '#';
	$arr_models = [];
	$arr_models = $html2->find('div[class*=List Multilist]', 0)->children();
	foreach ($arr_models as $model) {
		$name_model = '';
		$name_model = $model->find('.Header', 0)->plaintext;
		d($name_model);
		$arr_year_models = $model->find('.List .List');
		foreach ($arr_year_models as $div) {
			$name_engine_model = '';
			$name_engine_model = $div->find('a', 0)->plaintext;
			d($name_engine_model);
			$headers[0] = 'referer: ' . $url_market; 
			$url_modifications = $url0 . $div->find('a', 0)->href;
			$html3 = '';
			// d($name_year_model);
			$html3 = str_get_html(get_curl_result($url_modifications, $headers));  // страница выбора модификации и года 
			echo '#' , $url_modifications , '#';
			$arr_modifications = [];
			$arr_modifications = $html3->find('div[class*=List Multilist]', 0)->children();
			foreach ($arr_modifications as $modifications) {
				$name_modifications = '';
				$name_modifications = $modifications->find('.Header', 0)->plaintext;
				d($name_modifications);
				$arr_year = $modifications->find('.List .List');
				foreach ($arr_year as $year) {
					$name_year = '';
					$name_year = $year->find('a', 0)->plaintext;
					d($name_year);
					$headers[0] = 'referer: ' . $url_modifications; 
					$url_spareparts_groups = $url0 . $year->find('a', 0)->href;	
					$html_spareparts_groups = '';				
			 		$html_spareparts_groups = str_get_html(get_curl_result($url_spareparts_groups, $headers));
			 		echo '#' , $url_spareparts_groups , '#';
			 		$arr_spareparts_groups = [];
					$arr_spareparts_groups = $html_spareparts_groups->find('.List .List');
					foreach ($arr_spareparts_groups as $spareparts_group) {
						$name_group = '';
						$name_group = $spareparts_group->find('.name a', 0)->plaintext;
						$img_group = '';
						$img_group = $spareparts_group->find('img', 0);
						$img_group = is_object($img_group)? 'https:' . $img_group->src : 'Нет изображения' ;
						echo  $name_group, '&nbsp&nbsp' , '&nbsp&nbsp' , '<br>', "<img src=" . $img_group . " width='112' height='79'>";
						
						$url_spareparts_subgroups = '';
						$url_spareparts_subgroups = $url0 . $spareparts_group->find('.name a', 0)->href;
						$headers[0] = 'referer: ' . $url_spareparts_groups;
						$html_spareparts_subgroups = '';
						$html_spareparts_subgroups = str_get_html(get_curl_result($url_spareparts_subgroups, $headers));
						echo '#' , $url_spareparts_subgroups , '#';
					 	$arr_spareparts_subgroups = [];
						$arr_spareparts_subgroups = $html_spareparts_subgroups->find('.List .List');
						foreach ($arr_spareparts_subgroups as $spareparts_subgroup) {
							$name_subgroup = '';
							$name_subgroup = $spareparts_subgroup->find('.name a', 0)->plaintext;
							$img_subgroup = '';
							$img_subgroup = $spareparts_subgroup->find('img', 0);
							$img_subgroup = is_object($img_subgroup)? 'https:' . $img_subgroup->src : 'Нет изображения' ;
							echo  $name_subgroup, '&nbsp&nbsp' , '&nbsp&nbsp' , '<br>', "<img src=" . $img_subgroup . " width='112' height='79'>";
							$url_spareparts_selection = '';
							$url_spareparts_selection = $url0 . $spareparts_subgroup->find('a', 0)->href;
							$headers[0] = 'referer: ' . $url_spareparts_subgroups;
							$html_spareparts_selection = '';
							$html_spareparts_selection = str_get_html(get_curl_result($url_spareparts_selection, $headers));
							echo '#' , $url_spareparts_selection , '#';
						 	$img_selection = '';
						 	$img_selection = $html_spareparts_selection->find('.Images img',0);
						 	$img_selection = is_object($img_selection)? 'https:' . $img_selection->src : 'Нет изображения' ;
							$table_articls = '';
							echo  "<img src=" . $img_selection . " width='200' >";

							$table_articls = $html_spareparts_selection->find('.Info',0);
							$arr_sparepart_selection = $table_articls->find('[data-id]');
							foreach ($arr_sparepart_selection as $sparepart_selection) {					
								$position_part = '';
								if (is_object($sparepart_selection->find('.id', 0))) { // проверка есть ли связанные группы
									$position_part = get_data($sparepart_selection, '.id', 'plaintext');
								} else {
									$position_part = get_data($sparepart_selection, '.calloutText', 'plaintext');
								}	
								$number_part = '';
								$number_part = get_data($sparepart_selection, '.number a', 'plaintext', 'нет номера');
								$analog_number = '';
								$analog_number = get_data($sparepart_selection, '.analogNumber a', 'plaintext');
								$partAdditionalInfo_arr = [];						
								$partAdditionalInfo_arr = $sparepart_selection->find('.partAdditionalInfo a');
								if (count($partAdditionalInfo_arr) > 0) {
									foreach ($partAdditionalInfo_arr as $partAdditionalInfo) {
										$title = '';
										$title = trim($partAdditionalInfo->title);
										if (mb_strtolower($title) === 'изображение') {
											$headers[0] = 'referer: ' . $url_sparepart_selection;
											$img_part = '';
											$img_part = str_get_html(get_curl_result($url0 . $partAdditionalInfo->href, $headers));
											$img_part_src = '';
											$img_part_src = 'https:' . get_data($img_part, '.Image img', 'src');
											$img_part->clear(); // подчищаем за собой
											unset($img_part);	
											echo "<img src=" . $img_part_src . " width='200' height='200'>";
										}
									}
								}						
								$name_part = '';
								$name_part = get_data($sparepart_selection, '.name', 'plaintext', 'нет названия');
								$comment_part = '';
								$comment_part = get_data($sparepart_selection, '.comment', 'innertext');
								$usage_part = '';
								$usage_part = get_data($sparepart_selection, '.usage', 'innertext');
								$count_part = '';
								$count_part = get_data($sparepart_selection, '.cnt', 'plaintext');
								$dateRange_part = '';
								$dateRange_part = get_data($sparepart_selection, '.dateRange', 'plaintext');
								echo '<br>', $position_part , '&nbsp&nbsp' , $number_part ,  '&nbsp&nbsp' , $analog_number ,  '&nbsp&nbsp' , $name_part , '&nbsp&nbsp' , $comment_part , '&nbsp&nbsp' , $usage_part , '&nbsp&nbsp' , $count_part, '&nbsp&nbsp' , $dateRange_part , '<br>';	
							}
							$html_spareparts_selection->clear(); // подчищаем за собой
							unset($html_spareparts_selection);
							
							echo  '=========================================================================================================================================' , '<br>';
						}
						$html_spareparts_subgroups->clear(); // подчищаем за собой
						unset($html_spareparts_subgroups);
						
					}
					$html_spareparts_groups->clear(); // подчищаем за собой
					unset($html_spareparts_groups);	
				}
			}
			$html3->clear(); // подчищаем за собой
			unset($html3);
		}
		
	}
	$html2->clear(); // подчищаем за собой
	unset($html2);
}
$html1->clear(); // подчищаем за собой
unset($html1);

$mysqli->close();