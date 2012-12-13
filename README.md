check_core_api
==============
front:
http://api.z-dn.net/api/1/endpoints/es <- sukces, sparsowanie JSONa i sprawdzenie istnienia paru core'owych akcji
check_core_api -u URL -a ACTION1 -a ACTION2 -a ACTION3 ...
http://api.z-dn.net/api/1/endpoints/esdupa <-- ma zwracać porażkę, język nie istnieje
check_core_api -u URL -r
http://api.z-dn.net/api/1.2.2/endpoints/es <-- ma zwracać porażkę, wersja nie istnieje
check_core_api -u URL -r

data
http://es.data.api.z-dn.net/api/1/config/mobile_view <-- parse JSONa + czy HTTP 200
check_core_api -u URL
http://es.data.api.z-dn.net/api/1/tasks/mobile_index <-- parse JSONa sprawdzenie counta na itemsach w liście tasks (to jest widok zadań z głównej)
check_core_api -u URL -i MC:MW:XW:XC
http://es.data.api.z-dn.net/api/1/tasks/mobile_view/4 <-- widok zadania o ID 4, sprawdzenie czy istnieje data[0]task? + sprawdzenie paru kluczy ze środka + sprawdzenie czy istnieje datausers_data?.
check_core_api -u URL -t TASK1 -t TASK2 -k KEY1 -k KEY2 -d

autosuggester
http://es.suggest.z-dn.net/?q=a <-- czy zwraca > 0 wyników
check_core_api -u URL -q -
http://es.suggest.z-dn.net/?q=asdgadfhasdghashgsd <-- czy zwraca 0 wyników
check_core_api -u URL -q -0
http://es.suggest.z-dn.net/?q= <-- czy zwraca success false 
check_core_api -u URL -r