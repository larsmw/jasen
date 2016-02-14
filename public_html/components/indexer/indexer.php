<?php

/*
Term frequency
IDF

Entropy
Information Gain ?

Stemming : porterstemmer.

overfitting?

KNN
Euclidian Distance

cosine similarity?

*/

class Indexer extends Component {

  public function __construct() {
    // register paths to handle
    $this->register('content', 'index', array($this, "process"));
  }

  public function process($r, $e, $p) {
    //var_dump($p['content']);
    libxml_use_internal_errors(TRUE);
    $DOM = new DOMDocument();
    //load the html string into the DOMDocument
    $DOM->loadHTML($p['content']);
    foreach (libxml_get_errors() as $error) {
      // handle errors here
      // Here we get the structural errors in the downloaded pages.

      /*class LibXMLError#29 (6) {
	public $level =>
	int(2)
	public $code =>
	int(513)
	public $column =>
	int(44)
	public $message =>
	string(32) "ID T.C3.A9cnica already defined
	"
	public $file =>
	string(0) ""
	public $line =>
	int(109)
	}
      */
      //echo "xml error : " . $error->code . "\n";
    }
    libxml_use_internal_errors(FALSE);

    foreach($DOM->getElementsByTagName("h1") as $h) { 
      //echo "\nH1 : " . $h->nodeValue; 
    }
    foreach($DOM->getElementsByTagName("h2") as $h) { 
      //echo "\nH2 : " . $h->nodeValue; 
    }
    foreach($DOM->getElementsByTagName("h3") as $h) { 
      //echo "\nH3 : " . $h->nodeValue; 
    }
    foreach($DOM->getElementsByTagName("h4") as $h) { 
      //echo "\nH4 : " . $h->nodeValue; 
    }

    $script = $DOM->getElementsByTagName('script');

    $remove = [];
    foreach($script as $item)
      {
	$remove[] = $item;
      }

    foreach ($remove as $item)
      {
	$item->parentNode->removeChild($item); 
      }

    $html = $DOM->saveHTML();

    //asort(array_count_values(
    $doc = strip_tags($html, "<p><br>");

    // cpu...... bleeding...
    $classifier = new Classifier(new PersistentMemory());
    var_dump($classifier->guess($doc));
    $doc = $this->removeStopWords($doc);
    // TODO: stemming
    $words = array_count_values(str_word_count($doc, 1));
    arsort($words);
    //var_dump(array_slice($words,0,2));
  }

  private function removeStopWords($input){
    // EEEEEEK Stop words
    $commonWords = array('a','able','about','above','abroad','according','accordingly','across','actually','adj','after','afterwards','again','against','ago','ahead','ain\'t','all','allow','allows','almost','alone','along','alongside','already','also','although','always','am','amid','amidst','among','amongst','an','and','another','any','anybody','anyhow','anyone','anything','anyway','anyways','anywhere','apart','appear','appreciate','appropriate','are','aren\'t','around','as','a\'s','aside','ask','asking','associated','at','available','away','awfully','b','back','backward','backwards','be','became','because','become','becomes','becoming','been','before','beforehand','begin','behind','being','believe','below','beside','besides','best','better','between','beyond','both','brief','but','by','c','came','can','cannot','cant','can\'t','caption','cause','causes','certain','certainly','changes','clearly','c\'mon','co','co.','com','come','comes','concerning','consequently','consider','considering','contain','containing','contains','corresponding','could','couldn\'t','course','c\'s','currently','d','dare','daren\'t','definitely','described','despite','did','didn\'t','different','directly','do','does','doesn\'t','doing','done','don\'t','down','downwards','during','e','each','edu','eg','eight','eighty','either','else','elsewhere','end','ending','enough','entirely','especially','et','etc','even','ever','evermore','every','everybody','everyone','everything','everywhere','ex','exactly','example','except','f','fairly','far','farther','few','fewer','fifth','first','five','followed','following','follows','for','forever','former','formerly','forth','forward','found','four','from','further','furthermore','g','get','gets','getting','given','gives','go','goes','going','gone','got','gotten','greetings','h','had','hadn\'t','half','happens','hardly','has','hasn\'t','have','haven\'t','having','he','he\'d','he\'ll','hello','help','hence','her','here','hereafter','hereby','herein','here\'s','hereupon','hers','herself','he\'s','hi','him','himself','his','hither','hopefully','how','howbeit','however','hundred','i','i\'d','ie','if','ignored','i\'ll','i\'m','immediate','in','inasmuch','inc','inc.','indeed','indicate','indicated','indicates','inner','inside','insofar','instead','into','inward','is','isn\'t','it','it\'d','it\'ll','its','it\'s','itself','i\'ve','j','just','k','keep','keeps','kept','know','known','knows','l','last','lately','later','latter','latterly','least','less','lest','let','let\'s','like','liked','likely','likewise','little','look','looking','looks','low','lower','ltd','m','made','mainly','make','makes','many','may','maybe','mayn\'t','me','mean','meantime','meanwhile','merely','might','mightn\'t','mine','minus','miss','more','moreover','most','mostly','mr','mrs','much','must','mustn\'t','my','myself','n','name','namely','nd','near','nearly','necessary','need','needn\'t','needs','neither','never','neverf','neverless','nevertheless','new','next','nine','ninety','no','nobody','non','none','nonetheless','noone','no-one','nor','normally','not','nothing','notwithstanding','novel','now','nowhere','o','obviously','of','off','often','oh','ok','okay','old','on','once','one','ones','one\'s','only','onto','opposite','or','other','others','otherwise','ought','oughtn\'t','our','ours','ourselves','out','outside','over','overall','own','p','particular','particularly','past','per','perhaps','placed','please','plus','possible','presumably','probably','provided','provides','q','que','quite','qv','r','rather','rd','re','really','reasonably','recent','recently','regarding','regardless','regards','relatively','respectively','right','round','s','said','same','saw','say','saying','says','second','secondly','see','seeing','seem','seemed','seeming','seems','seen','self','selves','sensible','sent','serious','seriously','seven','several','shall','shan\'t','she','she\'d','she\'ll','she\'s','should','shouldn\'t','since','six','so','some','somebody','someday','somehow','someone','something','sometime','sometimes','somewhat','somewhere','soon','sorry','specified','specify','specifying','still','sub','such','sup','sure','t','take','taken','taking','tell','tends','th','than','thank','thanks','thanx','that','that\'ll','thats','that\'s','that\'ve','the','their','theirs','them','themselves','then','thence','there','thereafter','thereby','there\'d','therefore','therein','there\'ll','there\'re','theres','there\'s','thereupon','there\'ve','these','they','they\'d','they\'ll','they\'re','they\'ve','thing','things','think','third','thirty','this','thorough','thoroughly','those','though','three','through','throughout','thru','thus','till','to','together','too','took','toward','towards','tried','tries','truly','try','trying','t\'s','twice','two','u','un','under','underneath','undoing','unfortunately','unless','unlike','unlikely','until','unto','up','upon','upwards','us','use','used','useful','uses','using','usually','v','value','various','versus','very','via','viz','vs','w','want','wants','was','wasn\'t','way','we','we\'d','welcome','well','we\'ll','went','were','we\'re','weren\'t','we\'ve','what','whatever','what\'ll','what\'s','what\'ve','when','whence','whenever','where','whereafter','whereas','whereby','wherein','where\'s','whereupon','wherever','whether','which','whichever','while','whilst','whither','who','who\'d','whoever','whole','who\'ll','whom','whomever','who\'s','whose','why','will','willing','wish','with','within','without','wonder','won\'t','would','wouldn\'t','x','y','yes','yet','you','you\'d','you\'ll','your','you\'re','yours','yourself','yourselves','you\'ve','z','zero');
    
    $language_codes = array(
			    'en' => 'English' , 
			    'aa' => 'Afar' , 
			    'ab' => 'Abkhazian' , 
			    'af' => 'Afrikaans' , 
			    'am' => 'Amharic' , 
			    'ar' => 'Arabic' , 
			    'as' => 'Assamese' , 
			    'ay' => 'Aymara' , 
			    'az' => 'Azerbaijani' , 
			    'ba' => 'Bashkir' , 
			    'be' => 'Byelorussian' , 
			    'bg' => 'Bulgarian' , 
			    'bh' => 'Bihari' , 
			    'bi' => 'Bislama' , 
			    'bn' => 'Bengali/Bangla' , 
			    'bo' => 'Tibetan' , 
			    'br' => 'Breton' , 
			    'ca' => 'Catalan' , 
			    'co' => 'Corsican' , 
			    'cs' => 'Czech' , 
			    'cy' => 'Welsh' , 
			    'da' => 'Danish' , 
			    'de' => 'German' , 
			    'dz' => 'Bhutani' , 
			    'el' => 'Greek' , 
			    'eo' => 'Esperanto' , 
			    'es' => 'Spanish' , 
			    'et' => 'Estonian' , 
			    'eu' => 'Basque' , 
			    'fa' => 'Persian' , 
			    'fi' => 'Finnish' , 
			    'fj' => 'Fiji' , 
			    'fo' => 'Faeroese' , 
			    'fr' => 'French' , 
			    'fy' => 'Frisian' , 
			    'ga' => 'Irish' , 
			    'gd' => 'Scots/Gaelic' , 
			    'gl' => 'Galician' , 
			    'gn' => 'Guarani' , 
			    'gu' => 'Gujarati' , 
			    'ha' => 'Hausa' , 
			    'hi' => 'Hindi' , 
			    'hr' => 'Croatian' , 
			    'hu' => 'Hungarian' , 
			    'hy' => 'Armenian' , 
			    'ia' => 'Interlingua' , 
			    'ie' => 'Interlingue' , 
			    'ik' => 'Inupiak' , 
			    'in' => 'Indonesian' , 
			    'is' => 'Icelandic' , 
			    'it' => 'Italian' , 
			    'iw' => 'Hebrew' , 
			    'ja' => 'Japanese' , 
			    'ji' => 'Yiddish' , 
			    'jw' => 'Javanese' , 
			    'ka' => 'Georgian' , 
			    'kk' => 'Kazakh' , 
			    'kl' => 'Greenlandic' , 
			    'km' => 'Cambodian' , 
			    'kn' => 'Kannada' , 
			    'ko' => 'Korean' , 
			    'ks' => 'Kashmiri' , 
			    'ku' => 'Kurdish' , 
			    'ky' => 'Kirghiz' , 
			    'la' => 'Latin' , 
			    'ln' => 'Lingala' , 
			    'lo' => 'Laothian' , 
			    'lt' => 'Lithuanian' , 
			    'lv' => 'Latvian/Lettish' , 
			    'mg' => 'Malagasy' , 
			    'mi' => 'Maori' , 
			    'mk' => 'Macedonian' , 
			    'ml' => 'Malayalam' , 
			    'mn' => 'Mongolian' , 
			    'mo' => 'Moldavian' , 
			    'mr' => 'Marathi' , 
			    'ms' => 'Malay' , 
			    'mt' => 'Maltese' , 
			    'my' => 'Burmese' , 
			    'na' => 'Nauru' , 
			    'ne' => 'Nepali' , 
			    'nl' => 'Dutch' , 
			    'no' => 'Norwegian' , 
			    'oc' => 'Occitan' , 
			    'om' => '(Afan)/Oromoor/Oriya' , 
			    'pa' => 'Punjabi' , 
			    'pl' => 'Polish' , 
			    'ps' => 'Pashto/Pushto' , 
			    'pt' => 'Portuguese' , 
			    'qu' => 'Quechua' , 
			    'rm' => 'Rhaeto-Romance' , 
			    'rn' => 'Kirundi' , 
			    'ro' => 'Romanian' , 
			    'ru' => 'Russian' , 
			    'rw' => 'Kinyarwanda' , 
			    'sa' => 'Sanskrit' , 
			    'sd' => 'Sindhi' , 
			    'sg' => 'Sangro' , 
			    'sh' => 'Serbo-Croatian' , 
			    'si' => 'Singhalese' , 
			    'sk' => 'Slovak' , 
			    'sl' => 'Slovenian' , 
			    'sm' => 'Samoan' , 
			    'sn' => 'Shona' , 
			    'so' => 'Somali' , 
			    'sq' => 'Albanian' , 
			    'sr' => 'Serbian' , 
			    'ss' => 'Siswati' , 
			    'st' => 'Sesotho' , 
			    'su' => 'Sundanese' , 
			    'sv' => 'Swedish' , 
			    'sw' => 'Swahili' , 
			    'ta' => 'Tamil' , 
			    'te' => 'Tegulu' , 
			    'tg' => 'Tajik' , 
			    'th' => 'Thai' , 
			    'ti' => 'Tigrinya' , 
			    'tk' => 'Turkmen' , 
			    'tl' => 'Tagalog' , 
			    'tn' => 'Setswana' , 
			    'to' => 'Tonga' , 
			    'tr' => 'Turkish' , 
			    'ts' => 'Tsonga' , 
			    'tt' => 'Tatar' , 
			    'tw' => 'Twi' , 
			    'uk' => 'Ukrainian' , 
			    'ur' => 'Urdu' , 
			    'uz' => 'Uzbek' , 
			    'vi' => 'Vietnamese' , 
			    'vo' => 'Volapuk' , 
			    'wo' => 'Wolof' , 
			    'xh' => 'Xhosa' , 
			    'yo' => 'Yoruba' , 
			    'zh' => 'Chinese' , 
			    'zu' => 'Zulu' , 
			    );
    $str = preg_replace('/\b('.implode('|',$commonWords).')\b/i','',$input);
    $str = preg_replace('/\b('.implode('|',array_keys($language_codes)).')\b/i','',$str);

    return $str;
  }
}