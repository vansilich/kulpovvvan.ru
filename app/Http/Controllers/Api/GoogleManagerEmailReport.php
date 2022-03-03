<?php

namespace App\Http\Controllers\Api;

use App\Models\Manager;
use App\Models\NewEmailsManager;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Storage;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\Request;
use App\Helpers\Api\Mailganer;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Throwable;

class GoogleManagerEmailReport extends Controller
{

    /**
     * Handle the incoming request.
     *
     * @param Request $request
     */
    public function __invoke(Request $request)
    {
//        $data = $request->json()->all();
        $data = unserialize('a:2:{s:4:"data";a:342:{i:0;s:33:"klimentina.gapeeva@etl-company.ru";i:1;s:25:"skladchikova.oa@samzmi.ru";i:2;s:21:"purchasemmb@gmail.com";i:3;s:16:"os2235@yandex.ru";i:4;s:26:"TARASENKODA1@bn.rosneft.ru";i:5;s:23:"ijanbayeva@enermech.com";i:6;s:24:"e.aleksandrova@dalkos.ru";i:7;s:16:"3311@sstc.spb.ru";i:8;s:28:"kononovatn@metrowagonmash.ru";i:9;s:18:"av.se.62@gmail.com";i:10;s:23:"nata.sokolova88@list.ru";i:11;s:16:"eoa@arcticcon.ru";i:12;s:15:"m1@manometry.ru";i:13;s:23:"eugeniy.solod@gmail.com";i:14;s:23:"dudarenko@o1standard.ru";i:15;s:22:"PenevaIV@rusgeology.ru";i:16;s:20:"i.pavlova@indelta.ru";i:17;s:16:"super-skia@ya.ru";i:18;s:13:"sas@famaga.de";i:19;s:18:"rus-media@inbox.ru";i:20;s:17:"spk@os-electro.ru";i:21;s:23:"irina.tehnika01@mail.ru";i:22;s:16:"sales@egotech.ru";i:23;s:23:"ilya.kosyak11@yandex.ru";i:24;s:16:"otd14nvu@kulz.ru";i:25;s:18:"tech1@agrariy22.ru";i:26;s:19:"ivpopova@doorhan.ru";i:27;s:18:"sales@nastocke.com";i:28;s:21:"msk.sklad@npo-pas.com";i:29;s:25:"Agaidukova@ioscglobal.com";i:30;s:28:"e.shtrikmanis@himkompleks.ru";i:31;s:31:"aleksandr.kirillov@mashsteel.ru";i:32;s:16:"artyf@prolog.ltd";i:33;s:33:"fluidline@undeliverable.zenon.net";i:34;s:17:"tlc_group@mail.ru";i:35;s:15:"utngg@uralts.ru";i:36;s:15:"2061542@mail.ru";i:37;s:25:"kraynikova@himkompleks.ru";i:38;s:15:"snab1@ing.style";i:39;s:21:"ignatenko.ev@53cpi.ru";i:40;s:14:"info@tegasp.ru";i:41;s:19:"syaglov@sands.group";i:42;s:21:"a.cherkasov@becema.ru";i:43;s:21:"purchasepev@gmail.com";i:44;s:19:"transoilpro@mail.ru";i:45;s:13:"vrkz@inbox.ru";i:46;s:21:"denis.gatilov@rsce.ru";i:47;s:30:"O.Bashkirskaya@rg74.novatek.ru";i:48;s:26:"Gerasimov_NV@irkutskoil.ru";i:49;s:18:"e.mineev@siplus.ru";i:50;s:20:"lrv@rvdtechnoland.ru";i:51;s:17:"voronejtk@mail.ru";i:52;s:21:"sara-alex1980@mail.ru";i:53;s:28:"loginovng@halopolymer-kc.com";i:54;s:14:"gelvek@mail.ru";i:55;N;i:56;s:15:"kai@tek-know.ru";i:57;s:22:"a.solodov@chromatec.ru";i:58;s:21:"uk_omto7@turbo-don.ru";i:59;s:15:"zakupki@1grc.ru";i:60;s:19:"s.noskov@srsteam.ru";i:61;s:17:"kav@elkomplect.ru";i:62;s:15:"dzhun@yandex.ru";i:63;s:18:"vashchuk@npp-in.ru";i:64;s:17:"omtc4@ssoft24.com";i:65;s:22:"v.zolotnikov@pt.spb.ru";i:66;s:21:"biotechduks@gmail.com";i:67;s:18:"Finogeev@zaspl.com";i:68;s:28:"ivchenko@ingenium-company.ru";i:69;s:20:"SokolovAI@mos-gaz.ru";i:70;s:19:"mironov.v@gmail.com";i:71;s:17:"info@otsekatel.ru";i:72;s:18:"workizevsk@mail.ru";i:73;s:22:"Dmitriy.Grad@tescan.ru";i:74;s:28:"tatiana.lasareva@7techno.com";i:75;s:20:"invest_01otp@mail.ru";i:76;s:19:"office@disa-line.ru";i:77;s:18:"vsgolubkin@ciam.ru";i:78;s:19:"asman-kzn@yandex.ru";i:79;s:23:"profsnab.olga@yandex.ru";i:80;s:16:"malica.d@mail.ru";i:81;s:16:"ea@snabgermes.ru";i:82;s:25:"vlad.pastukhov.82@mail.ru";i:83;s:19:"dmitriev.gs@mail.ru";i:84;s:21:"kost.miheev@yandex.ru";i:85;s:22:"kriuchkov.ss@gmail.com";i:86;s:14:"vvv@aksprom.ru";i:87;s:21:"purchaseaab@gmail.com";i:88;s:19:"103@north-metall.ru";i:89;s:18:"ucmr-ots@yandex.ru";i:90;s:17:"snab1@chermet.com";i:91;s:18:"snab2@profkom44.ru";i:92;s:27:"Elvira.Belaya@vysotskspg.ru";i:93;s:11:"zsv@rdci.ru";i:94;s:15:"zosim.k@mail.ru";i:95;s:25:"zhurin@samaravolgomash.ru";i:96;s:23:"zhigulova2014@yandex.ru";i:97;s:17:"zharavin@s-zlk.ru";i:98;s:22:"zhakupov1987@gmail.com";i:99;s:21:"zavodimpuls@yandex.ru";i:100;s:17:"zau@eracomfort.ru";i:101;s:19:"zakupki@niotrade.ru";i:102;s:19:"zakupki.dlu@mail.ru";i:103;s:16:"zakupka@rt-in.ru";i:104;s:18:"zakupka1@losst.org";i:105;s:20:"zakupka191@yandex.ru";i:106;s:18:"zakaznpf@yandex.ru";i:107;s:11:"za@vls-i.ru";i:108;s:11:"z-ast@bk.ru";i:109;s:14:"yvy@kortekh.ru";i:110;s:17:"yuri@sitistroy.ru";i:111;s:27:"yakrov.m@profisantehnika.ru";i:112;s:18:"y.morozova@reph.ru";i:113;s:14:"vvm@energas.ru";i:114;s:21:"vts.vinsmsk@yandex.ru";i:115;s:11:"vth@mail.ru";i:116;s:12:"vt@torvis.ru";i:117;s:24:"vovazhurin1996@yandex.ru";i:118;s:14:"vov@a2delta.ru";i:119;s:21:"vorobyev@graz.sura.ru";i:120;s:22:"vodopianov_va@mosep.ru";i:121;s:20:"vmaslov@ural-test.ru";i:122;s:14:"vmarkov@ens.ru";i:123;s:18:"vlaskin@reatorg.ru";i:124;s:25:"vlaschenko@pharmawater.ru";i:125;s:28:"vladislav.kolesnikov@nami.ru";i:126;s:19:"vladim.sw@yandex.ru";i:127;s:22:"vjunik-a@aliter.spb.ru";i:128;s:20:"vishandrey@valant.ru";i:129;s:22:"viktor.abakumov@zti.ru";i:130;s:19:"vekdirect@gmail.com";i:131;s:15:"veell69@mail.ru";i:132;s:27:"vedyaev_dv@hms-neftemash.ru";i:133;s:26:"vbartenev@fluidbusiness.ru";i:134;s:12:"vav@gkers.ru";i:135;s:19:"vatregub8@gmail.com";i:136;s:20:"vasina.p.v@nporeg.ru";i:137;s:22:"vasiltsov_myu@kngf.org";i:138;s:14:"variya@mail.ru";i:139;s:14:"vargil@list.ru";i:140;s:25:"v_semenov@tnpz.rosneft.ru";i:141;s:27:"v.shabanov@unitechmarine.ru";i:142;s:24:"v.sadovnikov@aplisens.by";i:143;s:19:"v.krupa@cngmetan.ru";i:144;s:16:"v.gatsuk@tspc.ru";i:145;s:26:"v.fedorenko@polifasplus.ru";i:146;s:17:"v.1.r.u.s@mail.ru";i:147;s:16:"v-eremin@list.ru";i:148;s:16:"ural-kip@mail.ru";i:149;s:19:"ulivanov@spdbirs.ru";i:150;s:11:"u_d@mail.ru";i:151;s:19:"tulinox71@gmail.com";i:152;s:26:"tsvetkova_y@cleanmodule.ru";i:153;s:16:"tsimport@list.ru";i:154;s:20:"tsa@samaraproject.ru";i:155;s:21:"tretyyakova_ov@zid.ru";i:156;s:16:"top-pribor@bk.ru";i:157;s:18:"too.exw8@gmail.com";i:158;s:17:"tlk.shert@mail.ru";i:159;s:26:"titova_nv@hms-neftemash.ru";i:160;s:16:"titkovags@zpo.ru";i:161;s:27:"tihonova_ev@atlantis-pak.ru";i:162;s:18:"themakey@yandex.ru";i:163;s:17:"tf@almazgeobur.ru";i:164;s:18:"terehin-rk@mail.ru";i:165;s:22:"teplopribor-67@mail.ru";i:166;s:15:"tenneko@list.ru";i:167;s:20:"tender.box@naftan.by";i:168;s:19:"tehsnab-ekb@mail.ru";i:169;s:19:"tehservismp@list.ru";i:170;s:24:"tehm39.zakupky@gmail.com";i:171;s:27:"tehindustria.kaluga@mail.ru";i:172;s:16:"tedeks@yandex.ru";i:173;s:27:"technoplusproject@yandex.ru";i:174;s:23:"technology.chel@mail.ru";i:175;s:21:"tbuivolova@vdktech.ru";i:176;s:18:"tarasov@spdbirs.ru";i:177;s:22:"t78002006680@gmail.com";i:178;s:19:"t.petrova@deaxo.com";i:179;s:22:"t.nugaev@kst-energo.ru";i:180;s:27:"t.golumbevskiy@frame-spb.ru";i:181;s:27:"t.fedotova@msu-90.titan2.ru";i:182;s:24:"sydykov.islamiddin@bk.ru";i:183;s:19:"suvorov_dg@mosep.ru";i:184;s:27:"sushkova@energogazresurs.ru";i:185;s:23:"surov@monitoring-npo.ru";i:186;s:20:"suroegin_vs@mosep.ru";i:187;s:19:"surkovt1950@mail.ru";i:188;s:15:"str0784@mail.ru";i:189;s:24:"stolyarova.olga@konar.ru";i:190;s:21:"sthsr-zakupka@mail.ru";i:191;s:19:"stepanovao@pktba.ru";i:192;s:16:"sten67@yandex.ru";i:193;s:27:"starchikov@hms-neftemash.ru";i:194;s:11:"ssv70@ya.ru";i:195;s:17:"sst-100@yandex.ru";i:196;s:14:"ss@teksneva.ru";i:197;s:16:"ss@stm-tender.ru";i:198;s:16:"srmsnab2@mail.ru";i:199;s:18:"srg2@bvb-alyans.ru";i:200;s:14:"sr@air-part.ru";i:201;s:12:"sq@famaga.de";i:202;s:15:"souzgaz@mail.ru";i:203;s:23:"sorokinap@tepenergo.com";i:204;s:14:"somat9@mail.ru";i:205;s:15:"somat19@mail.ru";i:206;s:15:"soin@pneumax.ru";i:207;s:16:"snvavilov@irz.ru";i:208;s:16:"snabreom@mail.ru";i:209;s:20:"snab@xolodservice.ru";i:210;s:19:"snab@tehnopribor.ru";i:211;s:19:"snab@taun-energo.ru";i:212;s:19:"snab@rosmet-ural.ru";i:213;s:18:"snab@poyangaz.tech";i:214;s:17:"snab@piterbell.ru";i:215;s:16:"snab@mart-vlz.ru";i:216;s:16:"snab@hydrofab.ru";i:217;s:15:"snab@gkmp32.com";i:218;s:15:"snab@gkche23.ru";i:219;s:19:"snab@for-alumina.ru";i:220;s:14:"snab@evergr.ru";i:221;s:15:"snab@dttermo.ru";i:222;s:16:"snab@dekaterm.ru";i:223;s:21:"snab@ansercompany.com";i:224;s:13:"snab6@chkz.ru";i:225;s:19:"snab5@emi-kurgan.ru";i:226;s:21:"snab3@solur-russia.ru";i:227;s:16:"snab19@chzmek.ru";i:228;s:18:"snab-zebra@mail.ru";i:229;s:16:"smto@mmk-rus.com";i:230;s:17:"smcudm2@gmail.com";i:231;s:30:"skvortsova.katia2014@yandex.ru";i:232;s:18:"sksstroy@yandex.ru";i:233;s:18:"skrypnik@grasys.ru";i:234;s:20:"skill-energo@mail.ru";i:235;s:18:"sizov@ds-motors.ru";i:236;s:14:"sim76a@mail.ru";i:237;s:39:"silyanskaya.mariya@quaternion-group.com";i:238;s:24:"shuvalova_ov@boreas35.ru";i:239;s:14:"shumit@npge.ru";i:240;s:21:"shubina.d@4stihii.com";i:241;s:17:"shokov_ea@mail.ru";i:242;s:22:"shishkin.m.s@nporeg.ru";i:243;s:21:"shirobokov.av@ogmm.ru";i:244;s:23:"shekaleva.a@ehp-atom.ru";i:245;s:24:"sharibzhanov_rr@mosep.ru";i:246;s:17:"shamsi.30@mail.ru";i:247;s:28:"shabashov.io@metallprofil.ru";i:248;s:20:"shabalova_e@rt-3d.ru";i:249;s:17:"sha@exportural.kz";i:250;s:19:"sevrezerv43@mail.ru";i:251;s:13:"serov@bacs.ru";i:252;s:20:"serg-kazan91@mail.ru";i:253;s:15:"semin@vaceto.ru";i:254;s:17:"semen9753@mail.ru";i:255;s:27:"seleznev_ey@energystroy.com";i:256;s:18:"sdrqmail@gmail.com";i:257;s:16:"sav@kirscable.ru";i:258;s:24:"saturnvladimir@yandex.ru";i:259;s:17:"samonov86@mail.ru";i:260;s:19:"samigullin@s-zlk.ru";i:261;s:26:"samborskaya@dialkontech.ru";i:262;s:24:"sales@sakh-continent.com";i:263;s:25:"sales3@sakh-continent.com";i:264;s:13:"sale@asmvn.ru";i:265;s:20:"sakhalin@h-point.org";i:266;s:17:"snab@trest-cms.ru";i:267;s:19:"s.zentsov@pmpspb.ru";i:268;s:26:"s.timofeev@pnevmoresurs.ru";i:269;s:22:"s.sozinov@chromatec.ru";i:270;s:13:"s.logay@s7.ru";i:271;s:20:"s.kitaev@dekaterm.ru";i:272;s:20:"s.khairullina@rde.it";i:273;s:14:"ryrool@mail.ru";i:274;s:16:"rw3amc@yandex.ru";i:275;s:18:"rvd@smts-surgut.ru";i:276;s:15:"rvb.eka@mail.ru";i:277;s:19:"rusline2000@mail.ru";i:278;s:21:"ruselektrolis@mail.ru";i:279;s:28:"rusanova.tatiana92@yandex.ru";i:280;s:19:"romanovasv@gkars.ru";i:281;s:18:"rolik.weld@mail.ru";i:282;s:28:"rgamsahurdia@liman-trade.com";i:283;s:19:"resurs-ps@yandex.ru";i:284;s:17:"renat-xaz@mail.ru";i:285;s:19:"rashit.snab@zdnm.ru";i:286;s:32:"r.pauk@celestial-engineering.com";i:287;s:25:"r.nikonov@gasturbomash.ru";i:288;s:22:"r.minnibaev@raritek.ru";i:289;s:28:"Svetlana.Savchenkova@sgs.com";i:290;s:21:"pyankov.a.s@nporeg.ru";i:291;s:25:"purchase@generationrus.ru";i:292;s:20:"pump.service@mail.ru";i:293;s:25:"promtorg.dyakov@yandex.ru";i:294;s:22:"promstandart3@inbox.ru";i:295;s:16:"prom.t.v@mail.ru";i:296;s:24:"pripakhaylo.av@yandex.ru";i:297;s:15:"pon@tms-spb.com";i:298;s:19:"polevay17@yandex.ru";i:299;s:17:"pn.potapov@irz.ru";i:300;s:17:"pit.dvv@gmail.com";i:301;s:27:"pirozhkova.tv@khrunichev.ru";i:302;s:17:"petrov@ing-com.ru";i:303;s:14:"petrina@ghp.su";i:304;s:23:"pavel.dyak@bk-group.org";i:305;s:21:"pavel.brendel@ptpa.ru";i:306;s:26:"patyaeva_y@liman-trade.com";i:307;s:23:"parkerstore65@yandex.ru";i:308;s:20:"parigina@sibvinyl.ru";i:309;s:27:"panyuhin_ag@gazstroytech.ru";i:310;s:17:"panov@myorbita.ru";i:311;s:24:"overko_t@sibneftemash.ru";i:312;s:21:"otd541@aacprogress.ru";i:313;s:17:"otd251_7@npcap.ru";i:314;s:24:"oshestopalova@rgm-ngs.ru";i:315;s:14:"orghim@list.ru";i:316;s:21:"opt12@nhavtomatika.ru";i:317;s:17:"opora_vrn@mail.ru";i:318;s:14:"op2@novati.biz";i:319;s:18:"ooogrand18@list.ru";i:320;s:18:"ooo-gedeon@mail.ru";i:321;s:17:"oo.trio@yandex.ru";i:322;s:16:"omts@petroarm.ru";i:323;s:14:"omts@besteq.ru";i:324;s:24:"olga.zhurkina@hms-kkm.ru";i:325;s:20:"okorotenko@ustay.com";i:326;s:14:"oki-sm@mail.ru";i:327;s:16:"oge@npp-iskra.ru";i:328;s:22:"offisuvssnab@yandex.ru";i:329;s:16:"oas@nova-aqua.ru";i:330;s:26:"oa.namestnikova@redoct.biz";i:331;s:27:"o.solovieva@sidermotors.com";i:332;s:23:"o.shemchuk@zenit-kmz.ru";i:333;s:26:"o.kutukhina@globelectro.ru";i:334;s:21:"o.kandaruk@axitech.ru";i:335;s:30:"o.drovorubova@pharmasyntez.com";i:336;s:16:"nv@polimerkor.ru";i:337;s:15:"ns@at-energo.ru";i:338;s:17:"nrv@ms-service.su";i:339;s:17:"ARevenko2@slb.com";i:340;s:14:"nppbmt@mail.ru";i:341;s:23:"novoselov@steelimpex.ru";}s:9:"file_path";s:11:"other_51606";}');

        //лог исходных данных запроса
        $input_logger = Log::build(['driver' => 'single', 'path' => storage_path('logs/api/mailganer/GoogleManagerEmailReport/input.log') ]);
        //лог хода выполнения
        $debug_logger = Log::build(['driver' => 'single', 'path' => storage_path('logs/api/mailganer/GoogleManagerEmailReport/debug.log') ]);

        $input_logger->debug( serialize($data) );

        $data = !empty($data) ? $data : die();

        $total_not_founded = 0;
        $emails = $data['data'];
        $manager = explode('_', $data['file_path'])[0] ?? die();
        $today = (new Carbon())->format('Y.m.d');

        $managers = Manager::select('nickname', 'mailganer_list_id')->get()->toArray();
        $MainListId = Manager::select('mailganer_list_id')->where('nickname', '=', $manager)->first()->mailganer_list_id;

        Storage::append("/public/manager_stats/$today/report.txt","\n$manager - $today\n");

        foreach ($emails as $email) {

            $email = mb_strtolower($email, 'UTF-8');
            if ( !$email || !is_valid_email($email) ) continue;

            $is_found = false;
            foreach ($managers as $value) {

                try {

                    //обработка throttling ошибок функцией limitedFuncRetry()
                    $res = limitedFuncRetry(5, 2,
                        fn() => Mailganer::subscriberInfo( [ 'email' => $email, 'source' => $value['mailganer_list_id'] ] )
                    );
                } catch ( Throwable $exception ) {
                    $debug_logger->error( $exception->getMessage() );
                    continue;
                }

                if ($res->count != 0) {
                    $is_found = true;
                    break;
                }
            }

            // Если имейл не найден в списках (новый подписчик)
            if ( !$is_found ) {
                var_dump($email);
//                try {
//                    Mailganer::subscribeToList($email, $MainListId);
//                    $total_not_founded++;
//                    Storage::append("/public/manager_stats/$today/report.txt", $email);
//                } catch ( GuzzleException $exception ) {
//                    $debug_logger->error( $exception->getMessage() );
//                }
            }
        }

//        NewEmailsManager::create([
//            'manager' => $manager,
//            'count_new' => $total_not_founded,
//            'date' => $today,
//        ]);
    }
}
