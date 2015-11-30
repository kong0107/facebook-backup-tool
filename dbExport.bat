mongoexport --db facebook --collection %1 --out data/%1_private.json --jsonArray --query "{privacy:{$exists:true},'privacy.value':{$ne:'EVERYONE'}}"
mongoexport --db facebook --collection %1 --out data/%1_public.json --jsonArray --query "{$or:[{privacy:{$exists:false}},{'privacy.value':'EVERYONE'}]}"
