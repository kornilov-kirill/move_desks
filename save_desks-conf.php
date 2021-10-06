<?php

//NTLM CONFIG
$use_ntlm_auth = false;
$ntlm_user = 'Vlad'; // domain/user
$ntlm_password = 'QweAsdZxc123#@!';

$project_url = "https://office-map.ru/sibur_tobol251220";
$apiKey = "G89zjSIq6S49NQhDWg";

//AjaxAction.aspx, то, с помощью чего авторизуемся
$pre_resuest_url = 'https://office-map.ru/_engine/AjaxAction.aspx';
$pre_resuest_postdata = 'action=projectstartup&pagetype=&pageid=&appliedlayers=assets%2Cemployees%2Cunassigned+desks&projectname=sibur_tobol251220';
// SQL: select [desk_id] from [_Desks] WHERE ...тут условие, чтобы получить места только с определенного этажа
$desksids_list_url = "https://office-map.ru/_engine/List.aspx?action=getlistbody&listid=2021100670648047&editpaneltype=undefined&floorid=2021090123004432&projectname=sibur_tobol251220&_=1633524725148";
$assets_list_url = "";

$save_desks = true;
$save_assets = false;

$csv_delimiter = ";";
$csv_desks_filename = "desks.csv";
$csv_assets_filename = "assets.csv";
$csv_charset = 'utf-8';
