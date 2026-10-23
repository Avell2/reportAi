<?php
require __DIR__.'/../app/bootstrap.php';
$job=['symbol'=>'TEST','sources'=>['S1'=>['received_at'=>'2026-10-22T12:00:00Z']],'created_at'=>'2026-10-22T12:00:00Z','collection'=>[],'disclosures'=>[]];
$document=['sections'=>[['id'=>3,'paragraphs'=>[['text'=>'<script>test</script>','sources'=>[]],['text'=>'Unknown reference','sources'=>['S99']],['text'=>'']]]],'snapshot'=>array_fill(0,12,['label'=>'Zero','value'=>0])];
require __DIR__.'/../app/prepare_document.php';
if (!$document || count($document['snapshot'])!==12 || count($document['sections'][1]['paragraphs'])!==2 || $document['snapshot'][0]['value']!=='0') { exit("FAIL flexible content\n"); }
ob_start(); require __DIR__.'/../public/render_report.php'; $html=ob_get_clean();
if (str_contains($html,'<script>') || !str_contains($html,'&lt;script&gt;') || str_contains($html,'Development preview')) { exit("FAIL rendering\n"); }
foreach ([null,[],['sections'=>[['id'=>3,'paragraphs'=>[null,['text'=>[]]]]]]] as $empty) {
    $document=$empty; require __DIR__.'/../app/prepare_document.php';
    if ($document!==null) { exit("FAIL empty response\n"); }
}
echo "OK: partial sections, empty/unknown references, 12 indicators, zero, HTML escaping and empty responses.\n";
