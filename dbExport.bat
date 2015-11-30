mongoexport --db facebook --collection %1 --out data/%1_public.json --jsonArray --query "{$or:[{privacy:'everyone'},{'privacy.value':'EVERYONE'}]}"
mongoexport --db facebook --collection %1 --out data/%1_private.json --jsonArray --query "{privacy:{$ne:'everyone'},'privacy.value':{$ne:'EVERYONE'}}"
