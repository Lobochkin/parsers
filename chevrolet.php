<?
include '****/simple_html_dom.php';
$url0 = 'https://www.ilcats.ru';
$url = 'https://www.ilcats.ru/chevrolet/';
ini_set('memory_limit', '500M');
set_time_limit(0);
date_default_timezone_set("Europe/Moscow");
function d($var) 
{
    echo "<pre>";
    var_dump($var);
    echo "</pre>";
}

$server = "*****"; /* имя хоста (уточняется у провайдера), если работаем на локальном сервере, то указываем localhost */
$username = "*****";  // Имя пользователя БД 
$password = "*****"; /* Пароль пользователя, если у пользователя нет пароля то, оставляем пустым */
$database = "*****";
$db_table = "*****";
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
			echo '<div style="font-size: 0.7em;">Удаляем ' , trim($row['ip']) , '--> осталось ', (string)$count['COUNT(*)'] ,  ' --> пробуем: другой, текущее время ' , date("H:i:s") , ' </div>';			
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
$html1 = str_get_html(get_curl_result($url,$headers));// страница  моделей
$name_brand = '';
$name_brand = 'chevrolet'; 

$arr_models = [];
$arr_models = $html1->find('div[class*=List Multilist]', 0)->children();
foreach ($arr_models as $model) {
	$name_model = '';
	$name_model = $model->find('.Header', 0)->plaintext;
	d($name_model);
	
	$arr_year_models = $model->find('.List .List');
	foreach ($arr_year_models as $div) {
		$name_year = '';
		$name_year = $div->find('a', 0)->plaintext;
		d($name_year);
		
		$headers[0] = 'referer: ' . $url; 
		$url_spareparts_groups = $url0 . $div->find('a', 0)->href;
		$html2 = '';
		$html2 = str_get_html(get_curl_result($url_spareparts_groups, $headers));  // страница выбора группы з/ч
		echo '#' , $url_spareparts_groups , '#';
		$arr_spareparts_groups = $html2->find('.List .List');
		foreach ($arr_spareparts_groups as $spareparts_group) {
			$name_spareparts_group = '';
			$name_spareparts_group = $spareparts_group->find('a', 0)->plaintext;
			d($name_spareparts_group);

			$headers[0] = 'referer: ' . $url_spareparts_groups; 
			$url_spareparts_subgroups = $url0 . $spareparts_group->find('a', 0)->href;
			$html3 = '';
			$html3 = str_get_html(get_curl_result($url_spareparts_subgroups, $headers));  // страница выбора группы з/ч
			echo '#' , $url_spareparts_subgroups , '#';

			$arr_spareparts_subgroups = $html3->find('.List .List');
			foreach ($arr_spareparts_subgroups as $spareparts_subgroup) {
				$name_spareparts_subgroup = '';
				$name_spareparts_subgroup = $spareparts_subgroup->find('a', 0)->plaintext;
				d($name_spareparts_subgroup);

				$headers[0] = 'referer: ' . $url_spareparts_subgroups; 
				$url_spareparts_subgroup2 = $url0 . $spareparts_subgroup->find('a', 0)->href;
				$html4 = '';
				$html4 = str_get_html(get_curl_result($url_spareparts_subgroup2, $headers));  // страница выбора группы з/ч
				echo '#' , $url_spareparts_subgroup2 , '#';

				$arr_spareparts_subgroup2 = $html4->find('.Tiles .List .List');
				foreach ($arr_spareparts_subgroup2 as $spareparts_subgroup2) {
					$name_spareparts_subgroup2 = '';
					$name_spareparts_subgroup2 = $spareparts_subgroup2->find('.name a', 0)->plaintext;
					$img_spareparts_subgroup2 = '';
					$img_spareparts_subgroup2 = $spareparts_subgroup2->find('img', 0);
					$img_spareparts_subgroup2 = is_object($img_spareparts_subgroup2)? 'https:' . $img_spareparts_subgroup2->src : 'Нет изображения' ;
					echo  $name_spareparts_subgroup2, '&nbsp&nbsp' , '&nbsp&nbsp' , '<br>', "<img src=" . $img_spareparts_subgroup2 . " width='107' >";
					$url_spareparts_selection = '';
					$url_spareparts_selection = $url0 . $spareparts_subgroup2->find('a', 0)->href;
					$headers[0] = 'referer: ' . $url_spareparts_subgroup2;
					$html_spareparts_selection = '';
					$html_spareparts_selection = str_get_html(get_curl_result($url_spareparts_selection, $headers));
					echo '#' , $url_spareparts_selection , '#';
					// $html_spareparts_selection = str_get_html(get_curl_result('https://www.ilcats.ru/chevrolet/?function=getParts&market=GEN&modelcode=T&model=T02&year=2008&group=3&subgroup=1&partgroup=31100&image=RT3110'));
					$img_selection = '';
				 	$img_selection = $html_spareparts_selection->find('.Images img',0);
				 	$img_selection = is_object($img_selection)? 'https:' . $img_selection->src : 'Нет изображения' ;
					$table_articls = '';
					echo  "<img src=" . $img_selection . " width='200' >";
					$table_articls = $html_spareparts_selection->find('.Info',0);
					$arr_sparepart_selection = $table_articls->find('[data-id]');
					foreach ($arr_sparepart_selection as $sparepart_selection) {					
						$position_part = '';
						$position_part = get_data($sparepart_selection, '.calloutText', 'plaintext');
						$number_part = '';
						$number_part = get_data($sparepart_selection, '.number a', 'plaintext', 'нет номера');
						$replace_number = '';
						$replace_number = get_data($sparepart_selection, '.replaceNumber a', 'plaintext');
						$name_part = '';
						$name_part = get_data($sparepart_selection, '.name', 'plaintext', 'нет названия');
						$count_part = '';
						$count_part = get_data($sparepart_selection, '.count', 'plaintext');
						$dateRange_part = '';
						$dateRange_part = get_data($sparepart_selection, '.dateRange', 'plaintext');
						$usage_part = '';
						$usage_part = get_data($sparepart_selection, '.usage', 'innertext');
						echo '<br>', $position_part , '&nbsp&nbsp' , $number_part ,  '&nbsp&nbsp' , $replace_number ,  '&nbsp&nbsp' ,  '&nbsp&nbsp' , $name_part , '&nbsp&nbsp'  , '&nbsp&nbsp' , $usage_part , '&nbsp&nbsp' , $count_part, '&nbsp&nbsp' , $dateRange_part , '<br>';
					}
					$html_spareparts_selection->clear(); // подчищаем за собой
					unset($html_spareparts_selection);
				}
				$html4->clear(); // подчищаем за собой
				unset($html4);
				
			}			
			$html3->clear(); // подчищаем за собой
			unset($html3);
			
		}
		$html2->clear(); // подчищаем за собой
		unset($html2);
	}
}

$html1->clear(); // подчищаем за собой
unset($html1);

$mysqli->close();