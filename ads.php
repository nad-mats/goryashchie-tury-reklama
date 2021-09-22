<?php
set_time_limit(0);
ini_set("memory_limit", "-1");

//Настройки рекламной компании (РК)
$ad_campaign_uniq_version = 'v1'; //версия РК
$ad_min_keyword_length = 10; //минимальное количество символов в ключевом слове (рекомендуется от 10 символов)
$ad_keyword_match = 'Phrase match'; //тип соответсвия в РК (рекомендуется фразовое соответсвие)
$server_host_url = 'https://xn--c1aeixkcni4cza1b.com/'; //адрес вашего сайта
$output_folder = 'result/'; //папка с готовыми таблицами для Google.Ads


//Настройки таргетинга
$ad_cities_prefered = []; //Пустой массив задаёт все возможные города
$ad_countries_prefered = []; //Пустой массив задаёт все возможные страны
$ad_operators_prefered = []; //Пустой массив задаёт всех возможных туроператоров

//Пример таргетинга: Египет, Турция из Минска
$ad_cities_prefered = ['Минск'];
$ad_countries_prefered = ['Египет', 'Турция'];

//Все возможные варианты таргетинга по городам вылета с группировкой по странам
//$ad_cities_prefered = ['Минск', 'Гомель', 'Могилев', 'Витебск', 'Гродно', 'Брест'];
//$ad_cities_prefered = ['Алматы', 'Нур-Султан', 'Шымкент', 'Актобе', 'Караганда'];
//$ad_cities_prefered = ['Москва', 'Санкт-Петербург', 'Новосибирск', 'Екатеринбург', 'Нижний Новгород', 'Казань', 'Челябинск', 'Омск', 'Самара', 'Ростов-на-Дону', 'Уфа', 'Красноярск', 'Воронеж', 'Пермь', 'Волгоград', 'Краснодар'];
//$ad_cities_prefered = ['Киев', 'Харьков', 'Одесса', 'Днепр', 'Запорожье', 'Львов', 'Кривой Рог', 'Николаев', 'Мариуполь', 'Винница', 'Херсон', 'Черновцы', 'Ивано-Франковск'];

//Возможные варианты таргетинга по странам отдыха (полный список стран находится в файле base/countries.txt)
//$ad_countries_prefered = ['Египет', 'Турция', 'Кипр', 'Тунис', 'Албания', 'Болгария', 'Черногория', 'Испания', 'Италия', 'Греция'];



//////////////
//Основной код
//////////////

$ads_table = '';
$groups_table = '';
$keywords_table = '';
$campaigns_table = '';

$all_operators = file_get_contents('base/tour_operators.json');
$ad_geo_locations = file_get_contents('base/targeting.txt');

$all_operators_arr = json_decode($all_operators);
$ad_geo_locations_arr = explode(PHP_EOL, $ad_geo_locations);

//Обработка по направлениям
$etalon_cities_arr = array();
$cities_lines = explode("\n", file_get_contents('base/cities.txt'));
foreach($cities_lines as $cities_line) array_push($etalon_cities_arr, explode("|", $cities_line)[0]);

$etalon_countries_arr = array();
$countries_lines = explode("\n", file_get_contents('base/countries.txt'));
foreach($countries_lines as $countries_line) array_push($etalon_countries_arr, explode("|", $countries_line)[0]);

if(count($ad_cities_prefered) > 0) $etalon_cities_arr = $ad_cities_prefered;
foreach($etalon_cities_arr as $ad_city_from)
{
    $ad_geo_location = '';
    $ad_countries = [];
    $ad_operators = [];
    
    foreach($etalon_countries_arr as $ad_country_to)
    {
        foreach($all_operators_arr as $operator_arr)
        {        
            $operator_id = $operator_arr[0];
            $operator_directions = preg_replace('/[\(0-9\)]/', '', $operator_arr[3]);

            if(strpos($operator_directions, $ad_city_from.'|'.$ad_country_to) !== false)
            {
                if(in_array($operator_id, $ad_operators) === false) array_push($ad_operators, $operator_id);
                if(in_array($ad_country_to, $ad_countries) === false)
                {
                    if(count($ad_countries_prefered) == 0 || in_array($ad_country_to, $ad_countries_prefered) === true)
                    {
                        array_push($ad_countries, $ad_country_to);
                    }
                }
            }
        }
    }
    
    if(count($ad_countries) > 0 && count($ad_operators) > 0)
    {
        foreach($ad_geo_locations_arr as $ad_geo_location_line)
        {
            $line_arr = explode("|", $ad_geo_location_line);
            if($ad_city_from == $line_arr[0])
            {
                $ad_geo_location = trim($line_arr[1]);
                break 1;
            }
        }
        
        if($ad_geo_location != '')
        {
            $bad_hotels_arr = array();
            $good_hotels_arr = array();
            $good_hotels_full_arr = array();

            $hotels_directory = new RecursiveDirectoryIterator('base/hotels_base');
            $files_iterator = new RecursiveIteratorIterator($hotels_directory);
            foreach($files_iterator as $file_info)
            {
                if($file_info->isDir()) continue;
                
                $file_time = $file_info->getMTime();
                $file_path = dirname($file_info->getPathname().'/'.$file_info->getFilename());
                if(!strpos($file_path, '_temp') && !strpos($file_path, '_keys') && !strpos($file_path, '_names'))
                {
                    if(in_array(substr($file_info->getFilename(), 0, -4), $ad_operators) == true)
                    {
                        if(count($ad_operators_prefered) == 0 || in_array(substr($file_info->getFilename(), 0, -4), $ad_operators_prefered) == true)
                        {
                            $file_handle = fopen($file_path, 'r');
                            if($file_handle)
                            {
                                while(!feof($file_handle))
                                {
                                    $hotel_parts = explode("|", fgets($file_handle));
                                    if(count($hotel_parts) == 3)
                                    {
                                        $hotel_country = trim($hotel_parts[2]);
                                        $hotel_name_original = strtoupper($hotel_parts[0]);
                                        
                                        if(in_array($hotel_country, $ad_countries) == true)
                                        {
                                            //Не показываем контекстную рекламу по санаториям, гостевым домам, усадьбам, дешевым отелям без категории
                                            if(preg_match('/[1-5]\*|[1-5]\+|[1-5]\s\*|HV-[1,2]|HV[1,2]|\*{2,5}|[1-5]\sSTARS|[1-5]\sSTAR|\sHV|\sVILLA|\sВИЛЛА|\sАПАРТАМЕНТЫ|\sS\sCLASS|\sS-CLASS|\sBOUTIQUE|\sSPECIAL|\sAPARTMENT|\sAPART|\sAPART|\sAPTS|\sAPP|\sAPT/', $hotel_name_original, $categories_arr, PREG_OFFSET_CAPTURE) == 1)
                                            {
                                                $hotel_category = $categories_arr[0][0];
                                                $hotel_keyword = substr($hotel_name_original, 0, $categories_arr[0][1]);
                                                $resort_pos = strpos($hotel_keyword, '(');
                                                if($resort_pos > 0) $hotel_keyword = substr($hotel_keyword, 0, $resort_pos);
                                                
                                                //Для контекстной рекламы очень важно, чтобы ключевое слово не было слишком коротким и состояло минимум из двух слов, иначе мы будем сливать бюджет
                                                if(strlen($hotel_keyword) >= $ad_min_keyword_length)
                                                {
                                                    //В контекстной рекламе все бренды отклоняются
                                                    $brand_arr = ['TUI ', 'HILTON', 'KEMPINSKI', 'RENAISSANCE', 'RADISSON', 'WYNDHAM', 'ACCOR', 'GLORIA', 'CLARION', 'MANDARIN ORIENTAL',  'HOLIDAY INN'];
                                                    $is_brand = false;
                                                    foreach($brand_arr as $brand_name)
                                                    {
                                                        if(mb_strpos($hotel_name_original, $brand_name, 0, 'UTF-8') !== false)
                                                        {
                                                            $is_brand = true;
                                                            break;
                                                        }
                                                    }
                                                    
                                                    //if($is_brand == false)
                                                    if($is_brand == false)
                                                    {
                                                        if(preg_match('/\+|\/|^\.|^\(|\.{2,}|!{2,}|[0-9]|[А-я]/', $hotel_keyword, $bad_symbols_arr, PREG_OFFSET_CAPTURE) == 0)
                                                        {
                                                            $hotel_keyword = trim($hotel_keyword);
                                                            
                                                            //From Google Rules: Keywords cannot contain non-standard characters like: ! @ % * ,
                                                            $hotel_keyword = str_replace('"', '', $hotel_keyword);
                                                            $hotel_keyword = str_replace('!', '', $hotel_keyword);
                                                            $hotel_keyword = str_replace('@', '', $hotel_keyword);
                                                            $hotel_keyword = str_replace('%', '', $hotel_keyword);
                                                            $hotel_keyword = str_replace('*', '', $hotel_keyword);
                                                            $hotel_keyword = str_replace(',', ' ', $hotel_keyword);
                                                            $hotel_keyword = str_replace('-', ' ', $hotel_keyword);
                                                            $hotel_keyword = preg_replace('/\s+/', ' ', $hotel_keyword);
                                                            
                                                            $hotel_keyword_last_symbol = substr($hotel_keyword, -1, 1);
                                                            if($hotel_keyword_last_symbol == '&' || $hotel_keyword_last_symbol == '.') $hotel_keyword = substr($hotel_keyword, 0, strlen($hotel_keyword) - 1);
                                                            $hotel_keyword = trim($hotel_keyword);
                                                            
                                                            if(in_array($hotel_keyword, $good_hotels_arr) == false)
                                                            {
                                                                array_push($good_hotels_arr, $hotel_keyword);
                                                                array_push($good_hotels_full_arr, $hotel_keyword.'|'.$hotel_name_original.'|'.$hotel_category.'|'.$hotel_country);
                                                            }
                                                        }
                                                        else array_push($bad_hotels_arr, 'BAD SYMBOLS: '.$hotel_name_original);
                                                    }
                                                    else array_push($bad_hotels_arr, 'IS BRAND: '.$hotel_name_original);
                                                }
                                                else array_push($bad_hotels_arr, 'TOO SHOT: '.$hotel_name_original);
                                            }
                                            else array_push($bad_hotels_arr, 'NO CAT: '.$hotel_name_original);
                                        }
                                    }
                                }
                                fclose($file_handle);
                            }
                        }
                    }
                }
            }

            sort($good_hotels_full_arr);
            foreach($good_hotels_full_arr as $good_hotel)
            {
                $good_hotel_parts = explode('|', $good_hotel);
                if(count($good_hotel_parts == 4))
                {
                    $hotel_category_original = $good_hotel_parts[2];
                    $hotel_name_original = $good_hotel_parts[1];
                    $hotel_country = $good_hotel_parts[3];
                    $hotel_keyword = $good_hotel_parts[0];
                    
                    $hotel_keyword_all_lower = strtolower($hotel_keyword);
                    $ad_campaign = $ad_campaign_uniq_version.'_'.$ad_city_from.'_'.$hotel_country;
                    $hotel_keyword_first_upper = strtoupper(substr($hotel_keyword_all_lower, 0, 1)).substr($hotel_keyword_all_lower, 1);
                    
                    $hotel_category = '';
                    $ad_category = '';

                    if(preg_match('/[5-5]\*|[5-5]\+|[5-5]\s\*|HV-[2,2]|HV[2,2]|\*{5,5}|[5-5]\sSTARS|[5-5]\sSTAR/', $hotel_name_original) == 1)
                    {
                        $hotel_category = '5';
                        $ad_category = '5 звёзд';
                    }
                    else if(preg_match('/[4-4]\*|[4-4]\+|[4-4]\s\*|HV-[1,1]|HV[1,1]|\*{4,4}|[4-4]\sSTARS|[4-4]\sSTAR/', $hotel_name_original) == 1)
                    {
                        $hotel_category = '4';
                        $ad_category = '4 звезды';
                    }
                    else if(preg_match('/[3-3]\*|[3-3]\+|[3-3]\s\*|\*{3,3}|[3-3]\sSTARS|[3-3]\sSTAR/', $hotel_name_original) == 1)
                    {
                        $hotel_category = '3';
                        $ad_category = '3 звезды';
                    }
                    else if(preg_match('/[2-2]\*|[2-2]\+|[2-2]\s\*|\*{2,2}|[2-2]\sSTARS|[2-2]\sSTAR/', $hotel_name_original) == 1)
                    {
                        $hotel_category = '2';
                        $ad_category = '2 звезды';
                    }
                    
                    if($hotel_category != '')
                    {
                        //ADS FILE
                        $ad_final_url = $server_host_url.'?city='.rawurlencode($ad_city_from).'&country='.rawurlencode($hotel_country).'&hotel='.rawurlencode($hotel_keyword_first_upper).'&category='.rawurlencode($hotel_category);
                        $ad_path_1_url = $ad_city_from.'_'.$hotel_country; //_🥇 _✅
                        $ad_path_2_url = 'Горящие_Туры'; //_🌴
                        
                        $ad_path_1_url = 'Подбор_Тура'; //_🥇 _✅
                        $ad_path_2_url = 'Без_Агентств'; //_🌴
                        
                        $ad_header_1 = 'Горящие туры — '.$hotel_country;
                        $ad_header_2 = $hotel_keyword_first_upper;
                        $ad_header_3 = $ad_category;
                        
                        $ad_description_1 = $hotel_country.', '.$ad_category.'. Вылеты из г. '.$ad_city_from.'. Цены на ближайшие даты.';
                        $ad_description_2 = 'Мы сравниваем цены туроператоров и сервисов само бронирования. Узнайте, где дешевле!';
                        //$ad_description_2 = $hotel_name_original;
                        
                        if(mb_strlen($ad_header_2, 'UTF-8') > 30)
                        {
                            $ad_header_2 = '';
                            $ad_header_3 = '';
                            
                            $hotel_words_arr = explode(' ', $hotel_keyword_first_upper);
                            for($word_index = 0; $word_index < count($hotel_words_arr); $word_index++)
                            {
                                if(mb_strlen($ad_header_2.' '.$hotel_words_arr[$word_index], 'UTF-8') <= 30) $ad_header_2 = $ad_header_2.' '.$hotel_words_arr[$word_index];
                                else if(mb_strlen($ad_header_3.' '.$hotel_words_arr[$word_index], 'UTF-8') <= 30) $ad_header_3 = $ad_header_3.' '.$hotel_words_arr[$word_index];
                            }
                        }
                        
                        if(mb_strlen($ad_header_1, 'UTF-8') > 30) $ad_header_1 = mb_substr($ad_header_1, 0, 30, 'UTF-8');
                        if(mb_strlen($ad_header_2, 'UTF-8') > 30) $ad_header_2 = mb_substr($ad_header_2, 0, 30, 'UTF-8');
                        if(mb_strlen($ad_header_3, 'UTF-8') > 30) $ad_header_3 = mb_substr($ad_header_3, 0, 30, 'UTF-8');
                        if(mb_strlen($ad_description_1, 'UTF-8') > 90) $ad_description_1 = mb_substr($ad_description_1, 0, 90, 'UTF-8');
                        if(mb_strlen($ad_description_2, 'UTF-8') > 90) $ad_description_2 = mb_substr($ad_description_2, 0, 90, 'UTF-8');
                        
                        //ADS
                        if($ads_table == '') $ads_table = 'Ad group,Headline 1,Headline 2,Headline 3,Description,Description 2,Campaign,Ad type,Ad status,Final URL,Path 1,Path 2';
                        $ads_table .= PHP_EOL.$hotel_keyword_all_lower.',"'.$ad_header_1.'","'.$ad_header_2.'","'.$ad_header_3.'","'.$ad_description_1.'","'.$ad_description_2.'",'.$ad_campaign.',Expanded text ad,Enabled,'.$ad_final_url.','.$ad_path_1_url.','.$ad_path_2_url;
                        
                        //GROUPS
                        if($groups_table == '') $groups_table = 'Ad group,Campaign,Ad group type,Ad group status';
                        $groups_table .= PHP_EOL.$hotel_keyword_all_lower.','.$ad_campaign.',Standard,Enabled';
                        
                        //KEYWORDS
                        if($keywords_table == '') $keywords_table = 'Keyword,Ad group,Match type,Campaign,Campaign type,Bid strategy type,Keyword status';
                        $keywords_table .= PHP_EOL.$hotel_keyword_all_lower.','.$hotel_keyword_all_lower.','.$ad_keyword_match.','.$ad_campaign.',Search,Maximize clicks,Enabled';
                        
                        //CAMPAIGNS
                        if($campaigns_table == '') $campaigns_table = 'Campaign,Budget,Campaign type,Networks,Location,Bid strategy type,Campaign status';
                        $campaign_line = $ad_campaign.',100.00,Search,Google search;Search partners,"'.$ad_geo_location.'",Maximize clicks,Paused';
                        if(strpos($campaigns_table, $ad_campaign) == false) $campaigns_table .= PHP_EOL.$campaign_line;
                    }
                    else echo 'Can not find category (we very need category for setting search param): '.$hotel_name_original.'<br>';
                }
            }

            echo '<b>'.$ad_city_from.'</b> ('.$ad_geo_location.'): '. implode(', ', $ad_countries).'. Активные туроператоры: '. implode(', ', $ad_operators).'. ';
            echo 'Количество уникальных ключевых фраз: <b>'.count($good_hotels_arr).'</b>, было отброшено <b>'.count($bad_hotels_arr).'</b> отелей с короткими (<'.$ad_min_keyword_length.' символов), неуникальными или неподходящими названиями без четко выявленной категории.<br><br>';
            
            //sort($bad_hotels_arr);
            //foreach($bad_hotels_arr as $hotel) echo $hotel.'<br>';
        }
        else echo 'Skipping city: geo location for '.$ad_city_from.' is not found.<br><br>';
    }
    else echo '<b>'.$ad_city_from.'</b>: активных туроператоров <b>нет</b>.<br><br>';
}

//Запись готовых таблиц
$ads_file = '3_google_ads.csv';
$groups_file = '1_google_groups.csv';
$keywords_file = '2_google_keywords.csv';
$campaigns_file = '0_google_campaigns.csv';
$handle_1 = fopen($output_folder.$ads_file, 'w'); fwrite($handle_1, $ads_table); fclose($handle_1);
$handle_2 = fopen($output_folder.$groups_file, 'w'); fwrite($handle_2, $groups_table); fclose($handle_2);
$handle_3 = fopen($output_folder.$keywords_file, 'w'); fwrite($handle_3, $keywords_table); fclose($handle_3);
$handle_4 = fopen($output_folder.$campaigns_file, 'w'); fwrite($handle_4, $campaigns_table); fclose($handle_4);
?>