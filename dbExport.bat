mongoexport --db facebook --collection %1 --out data/%1_public.json --jsonArray --query "{$or:[{privacy:{$exists:false}},{privacy:'everyone'},{'privacy.value':'EVERYONE'}]}"
mongoexport --db facebook --collection %1 --out data/%1_private.json --jsonArray --query "{privacy:{$exists:true,$ne:'everyone'},'privacy.value':{$ne:'EVERYONE'}}"
