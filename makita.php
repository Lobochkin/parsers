<?php
ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require_once 'simple_html_dom.php';
ini_set('memory_limit', '500M');
set_time_limit(0);

$url = "https://makita-line.ru";

function getCurlResult ($url) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION,1);
	curl_setopt($ch, CURLPROTO_HTTPS,1);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	$htmltext = curl_exec($ch);
	curl_close($ch);
	$htmltext = iconv("CP1251", "UTF-8", $htmltext);
	if (strlen($htmltext) == 0) {
		echo "пустой curl, url:" , $url;
	}
	return $htmltext;
}


function d($var) 
{
    echo "<pre>";
    var_dump($var);
    echo "</pre>";
}

function delete_nbsp($text)
{
	$text = trim(str_replace(chr(194).chr(160), ' ', html_entity_decode($text)));
	return $text;
}

$arr_string_delete = [
	"Для покупки",
	"Чтобы приобрести",
	"Чтобы получить",
	"Чтобы заказать",
	"Для заказа",
	"Ознакомившись",
	"Для приобретения",
	"Приобрести",
	"В нашей компании",
	"Чтобы его купить ",
	"Простейшим способом",
	"Самым простым",
	"Чтобы без лишних",
	"Приобретение данного",
	"Самым простым",
	"Оптимальным способом",
	"Заказать",
	"При желании",
	"В компании",
	"В нашем",
	"Приобретение",
	"Чтобы циркулярку",
	"интернет-магазин",
	"При необходимости купить",
	"Чтобы купить",
	"Наша компания",
	"show_product_description",
];

function clean_str($text) // функция очистки текста у блока информации о товаре
{
	global $arr_string_delete;
	$text = str_replace('Описание', '', $text);
	$text = str_replace('<br>', '', $text);
	$str = strpos($text, '<h2>Видео');
	$text = $str? substr($text, 0, $str): $text ;
	$text = preg_replace('/<span[^<>]+?>([^<>]*?)<\s?\/span\s?>/', '$1', $text);
	$text = preg_replace('/<a[^<>]+?>([^<>]*?)<\s?\/a\s?>/', '$1', $text);
	$text = preg_replace('/<b\s?>(.+?)<\s?\/b\s?>/', '', $text);
	foreach ($arr_string_delete as $string_delete) {		
		$text = preg_replace('/<\s?([a-z]{1,6})[^<>]*?>[^<>]*?' . preg_quote($string_delete, '/') . '[^\1]*?<\s?\/\s?\1\s?>/uis', '', $text);
	}
	return $text;
}

$attention_div = '<div style="color:red;">Внимание! Инструмент поставляется без аккумулятора <br>и зарядного устройства</div>';

$url_arr_items = [];

$zz = 0;
$html = str_get_html(getCurlResult($url));
$html_arr = $html->find('.moduletable .mainlevel');// каталог верхнего уровня
foreach ($html_arr as $a) {
	$link = $a->href;
	$url1 = $url . $link;
	$html1 = str_get_html(getCurlResult($url1)); //каталог первого уровня
	$html_arr1 = $html1->find('.sublevel');
	if (count($html_arr1)) {// проверка на вложенность каталогов
		foreach ($html_arr1 as $a1) {
			$link1 = $a1->href;
			$url2 = $url . $link1;
			d($url2);
			
			$html2= str_get_html(getCurlResult($url2)); //каталог второго уровня
			$html_arr3 = $html2->find('.cat_div_t');
			if (count($html_arr3)) {// проверка на вложенность каталогов
				foreach ($html_arr3 as $div) {
					// $item_name2 = $div->find('a', 0)->title;
					$link2 = $div->find('a', 0)->href;
					$url3 = $url . $link2;
					$html3= str_get_html(getCurlResult($url3)); //каталог третьего уровня
					$html_arr4 = $html3->find('.cat_div_t');
					if (count($html_arr4)) {// проверка на вложенность каталогов
						foreach ($html_arr4 as $div1) {
							$link3 = $div1->find('a', 0)->href;
							$url4 = $url . $link3;
							$html4= str_get_html(getCurlResult($url4)); //каталог четвертого уровня
							$html_arr4 = $html4->find('.cat_div_t');
							if (count($html_arr4)) {// проверка на вложенность каталогов
								foreach ($html_arr4 as $div2) {
									$link4 = $div2->find('a', 0)->href;
									$url5 = $url . $link4;
									$html5= str_get_html(getCurlResult($url5)); //каталог пятого уровня
									echo (count($html5->find('.cat_div_t'))?"<h1>есть каталоги шестого уровня</h1>":"");
									$html_arr6 = $html5->find('.items_catalog_one_item');
									foreach ($html_arr6 as $a6) {
										$href = '';
										$href = $a6->find('.items_catalog_one_item_header a', 0)->href;
										if (strpos($href, $url) === false) {
											$url_arr_items[] = $url . $href;
											echo $url . $href;
										}
									}
									$html5->clear(); // подчищаем за собой
									unset($html5);
								}
							} else {
								$html_arr5 = $html4->find('.items_catalog_one_item');
								foreach ($html_arr5 as $a5) {
									$href = '';
									$href = $a5->find('.items_catalog_one_item_header a', 0)->href;
									if (strpos($href, $url) === false) {
										$url_arr_items[] = $url . $href;
										echo $url . $href;
									}
								}
							}
							$html4->clear(); // подчищаем за собой
							unset($html4);
						}
					} else {
						$html_arr5 = $html3->find('.items_catalog_one_item');
						foreach ($html_arr5 as $a4) {
							$href = '';
							$href = $a4->find('.items_catalog_one_item_header a', 0)->href;
							if (strpos($href, $url) === false) {
								$url_arr_items[] = $url . $href;
								echo $url . $href;
							}
						}
					}
					$html3->clear(); // подчищаем за собой
					unset($html3);
				}
			} else {
				$html_arr2 = $html2->find('.items_catalog_one_item');
				foreach ($html_arr2 as $a3) {
					$href = '';
					$href = $a3->find('.items_catalog_one_item_header a', 0)->href;
					if (strpos($href, $url) === false) {
						$url_arr_items[] = $url . $href;
						echo $url . $href;
					}
				}
			}
			$html2->clear(); // подчищаем за собой
			unset($html2);
		}

	} else {
		$html_arr_p1 = $html1->find('.items_catalog_one_item');
		foreach ($html_arr_p1 as $a2) {
			$href = '';
			$href = $a2->find('.items_catalog_one_item_header a', 0)->href;			
			if (strpos($href, $url) === false) {
				$url_arr_items[] = $url . $href;
				echo $url . $href;
			}
		}
	}
	$html1->clear(); // подчищаем за собой
	unset($html1);
}

$html->clear(); // подчищаем за собой
unset($html);

$url_arr_items = array_unique($url_arr_items);
foreach ($url_arr_items as $url) {
	$url0 = '';
	$url0 = 'https:';
	$html = '';
	$html = str_get_html(getCurlResult($url)); // карточка товара
	$name_product = '';
	$name_product = $html->find('h1', 0)->plaintext;
	$name_product = delete_nbsp($name_product);
	$href_img = '';
	$href_img = $html->find('#a_img_full_image', 0);
	$href_img = (is_object($href_img))? $url0 . $href_img->href : 'нет изображения';
	$attention = '';
	$attention = getCurlResult($url);
	$attention = (strpos($attention, 'Внимание! Инструмент поставляется без аккумулятора') !== false)? $attention_div : '';
	$description = '';
	$description = $html->find('#product_description td', 0);
	$description = (is_object($description))? $description->innertext:'Описания нет';
	if ($description !== 'Описания нет') {
		$description = clean_str($description);
		$description = delete_nbsp($description);
		$description = preg_replace('/<iframe.*iframe>/', '', $description);
		preg_match('/[а-я]/uis', $description, $m);
		$description = $m? $description . '</div>' :'Описания нет';
	}
	$description = $attention . $description;
	$characteristic_arr = [];
	$characteristic = '';
	$characteristic_arr = $html->find('#technical_parameters td table', 0);
	if (strpos($characteristic_arr->plaintext, 'Цена за безналичный расчет:') === false) { // проверяем что первая таблица это не другие товары а именно характеристика
		$characteristic_arr = $characteristic_arr->find('tr td');
		foreach ($characteristic_arr as $key => $td) {
			if (is_object($td) && !is_object($td->find('strong', 0))) {
				$characteristic .= ($key % 2 !== 0)? $td->plaintext . ':': $td->plaintext . '<br>';
			}
		}
	} else {
		$characteristic = 'нет характеристики';
	}
	mysqli_query($mysqllink, "call ********* ('Makita', '', '', '', '', '', '".$name_product."', '".$href_img."', '".$characteristic."', '".$description."');"); ClearRecordsets();
	echo  $name_product , '---', $href_img , '---' , $description , '---', $characteristic , '=============================================================================================================================<br>';
	$html->clear(); // подчищаем за собой
	unset($html);
}

?>
