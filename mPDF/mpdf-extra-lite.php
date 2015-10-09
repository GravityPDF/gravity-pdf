<?php

// ******************************************************************************
// Software: mPDF, Unicode-HTML Free PDF generator                              *
// Version:  5.6     based on                                                   *
//           FPDF by Olivier PLATHEY                                            *
//           HTML2FPDF by Renato Coelho                                         *
// Date:     2013-01-20                                                         *
// Author:   Ian Back <ianb@bpm1.com>                                           *
// License:  GPL                                                                *
//                                                                              *
// Changes:  See changelog.txt                                                  *
// ******************************************************************************


define('mPDF_VERSION','5.6');

//Scale factor
define('_MPDFK', (72/25.4));

define('AUTOFONT_CJK',1);
define('AUTOFONT_THAIVIET',2);
define('AUTOFONT_RTL',4);
define('AUTOFONT_INDIC',8);
define('AUTOFONT_ALL',15);

define('_BORDER_ALL',15);
define('_BORDER_TOP',8);
define('_BORDER_RIGHT',4);
define('_BORDER_BOTTOM',2);
define('_BORDER_LEFT',1);

if (!defined('_MPDF_PATH')) define('_MPDF_PATH', dirname(preg_replace('/\\\\/','/',__FILE__)) . '/');
if (!defined('_MPDF_URI')) define('_MPDF_URI',_MPDF_PATH);

require_once(_MPDF_PATH.'includes/functions.php');
require_once(_MPDF_PATH.'config_cp.php');

if (!defined('_JPGRAPH_PATH')) define("_JPGRAPH_PATH", _MPDF_PATH.'jpgraph/');

if (!defined('_MPDF_TEMP_PATH')) define("_MPDF_TEMP_PATH", _MPDF_PATH.'tmp/');

if (!defined('_MPDF_TTFONTPATH')) { define('_MPDF_TTFONTPATH',_MPDF_PATH.'ttfonts/'); }
if (!defined('_MPDF_TTFONTDATAPATH')) { define('_MPDF_TTFONTDATAPATH',_MPDF_PATH.'ttfontdata/'); }

$errorlevel=error_reporting();
$errorlevel=error_reporting($errorlevel & ~E_NOTICE);

//error_reporting(E_ALL);

if(function_exists("date_default_timezone_set")) {
	if (ini_get("date.timezone")=="") { date_default_timezone_set("Europe/London"); }
}
if (!function_exists("mb_strlen")) { die("Error - mPDF requires mb_string functions. Ensure that PHP is compiled with php_mbstring.dll enabled."); }

if (!defined('PHP_VERSION_ID')) {
    $version = explode('.', PHP_VERSION);
    define('PHP_VERSION_ID', ($version[0] * 10000 + $version[1] * 100 + $version[2]));
}
// Machine dependent number of bytes used to pack "double" into binary (used in cacheTables)
$test = pack("d", 134455.474557333333666);
define("_DSIZE", strlen($test));

class mPDF
{

///////////////////////////////
// EXTERNAL (PUBLIC) VARIABLES
// Define these in config.php
///////////////////////////////
// mPDF 5.6.01
var $layers;
var $current_layer;
var $open_layer_pane;

var $margBuffer;	// mPDF 5.4.04
var $splitTableBorderWidth;	// mPDF 5.4.16

var $cacheTables;
var $bookmarkStyles;
var $useActiveForms;

var $repackageTTF;
var $allowCJKorphans;
var $allowCJKoverflow;

var $useKerning;
var $restrictColorSpace;
var $bleedMargin;
var $crossMarkMargin;
var $cropMarkMargin;
var $cropMarkLength;
var $nonPrintMargin;

var $PDFX;
var $PDFXauto;

var $PDFA;
var $PDFAauto;
var $ICCProfile;

var $printers_info;
var $iterationCounter;
var $smCapsScale;
var $smCapsStretch;

var $backupSubsFont;
var $backupSIPFont;
var $debugfonts;
var $useAdobeCJK;
var $percentSubset;
var $maxTTFFilesize;
var $BMPonly;

var $tableMinSizePriority;

var $dpi;
var $watermarkImgAlphaBlend;
var $watermarkImgBehind;
var $justifyB4br;
var $packTableData;
var $pgsIns;
var $simpleTables;
var $enableImports;

var $debug;
var $showStats;
var $setAutoTopMargin;
var $setAutoBottomMargin;
var $autoMarginPadding;
var $collapseBlockMargins;
var $falseBoldWeight;
var $normalLineheight;
var $progressBar;
var $incrementFPR1;
var $incrementFPR2;
var $incrementFPR3;
var $incrementFPR4;

var $hyphenate;
var $hyphenateTables;
var $SHYlang;
var $SHYleftmin;
var $SHYrightmin;
var $SHYcharmin;
var $SHYcharmax;
var $SHYlanguages;
// PageNumber Conditional Text
var $pagenumPrefix;
var $pagenumSuffix;
var $nbpgPrefix;
var $nbpgSuffix;
var $showImageErrors;
var $allow_output_buffering;
var $autoPadding;
var $useGraphs;
var $autoFontGroupSize;
var $tabSpaces;
var $useLang;
var $restoreBlockPagebreaks;
var $watermarkTextAlpha;
var $watermarkImageAlpha;
var $watermark_size;
var $watermark_pos;
var $annotSize;
var $annotMargin;
var $annotOpacity;
var $title2annots;
var $keepColumns;
var $keep_table_proportions;
var $ignore_table_widths;
var $ignore_table_percents;
var $list_align_style;
var $list_number_suffix;
var $useSubstitutions;
var $CSSselectMedia;

var $forcePortraitHeaders;
var $forcePortraitMargins;
var $displayDefaultOrientation;
var $ignore_invalid_utf8;
var $allowedCSStags;
var $onlyCoreFonts;
var $allow_charset_conversion;

var $jSWord;
var $jSmaxChar;
var $jSmaxCharLast;
var $jSmaxWordLast;

var $orphansAllowed;
var $max_colH_correction;


var $table_error_report;
var $table_error_report_param;
var $biDirectional;
var $text_input_as_HTML;
var $anchor2Bookmark;
var $list_indent_first_level;
var $shrink_tables_to_fit;

var $allow_html_optional_endtags;

var $img_dpi;

var $defaultheaderfontsize;
var $defaultheaderfontstyle;
var $defaultheaderline;
var $defaultfooterfontsize;
var $defaultfooterfontstyle;
var $defaultfooterline;
var $header_line_spacing;
var $footer_line_spacing;

var $pregUHCchars;
var $pregSJISchars;
var $pregCJKchars;
var $pregASCIIchars1;
var $pregASCIIchars2;
var $pregASCIIchars3;
var $pregVIETchars;
var $pregVIETPluschars;

var $pregRTLchars;
var $pregHEBchars;
var $pregARABICchars;
var $pregNonARABICchars;
// INDIC
var $pregHIchars;
var $pregBNchars;
var $pregPAchars;
var $pregGUchars;
var $pregORchars;
var $pregTAchars;
var $pregTEchars;
var $pregKNchars;
var $pregMLchars;
var $pregSHchars;
var $pregINDextra;

var $mirrorMargins;
var $default_lineheight_correction;
var $watermarkText;
var $watermarkImage;
var $showWatermarkText;
var $showWatermarkImage;

var $fontsizes;

// Aliases for backward compatability
var $UnvalidatedText;	// alias = $watermarkText
var $TopicIsUnvalidated;	// alias = $showWatermarkText
var $useOddEven;		// alias = $mirrorMargins
var $useSubstitutionsMB;	// alias = $useSubstitutions



//////////////////////
// CLASS OBJECTS
//////////////////////
var $grad;
var $bmp;
var $wmf;
var $tocontents;
var $form;
var $directw;

//////////////////////
// INTERNAL VARIABLES
//////////////////////
var $inMeter;	// mPDF 5.5.09

var $CJKleading;
var $CJKfollowing;
var $CJKoverflow;

var $textshadow;

var $colsums;
var $spanborder;
var $spanborddet;

var $visibility;

var $useRC128encryption;
var $uniqid;

var $kerning;
var $fixedlSpacing;
var $minwSpacing;
var $lSpacingCSS;
var $wSpacingCSS;

var $listDir;
var $spotColorIDs;
var $SVGcolors;
var $spotColors;
var $defTextColor;
var $defDrawColor;
var $defFillColor;

var $tableBackgrounds;
var $inlineDisplayOff;
var $kt_y00;
var $kt_p00;
var $upperCase;
var $checkSIP;
var $checkSMP;
var $checkCJK;
var $tableCJK;

var $watermarkImgAlpha;
var $PDFAXwarnings;
var $MetadataRoot;
var $OutputIntentRoot;
var $InfoRoot;
var $current_filename;
var $parsers;
var $current_parser;
var $_obj_stack;
var $_don_obj_stack;
var $_current_obj_id;
var $tpls;
var $tpl;
var $tplprefix;
var $_res;

var $pdf_version;
var $noImageFile;
var $lastblockbottommargin;
var $baselineC;
var $subPos;
var $subArrMB;
var $ReqFontStyle;
var $tableClipPath ;
var $forceExactLineheight;
var $listOcc;

var $fullImageHeight;
var $inFixedPosBlock;		// Internal flag for position:fixed block
var $fixedPosBlock;		// Buffer string for position:fixed block
var $fixedPosBlockDepth;
var $fixedPosBlockBBox;
var $fixedPosBlockSave;
var $maxPosL;
var $maxPosR;

var $loaded;

var $extraFontSubsets;
var $docTemplateStart;		// Internal flag for page (page no. -1) that docTemplate starts on
var $time0;

// Classes
var $indic;
var $barcode;

var $SHYpatterns;
var $loadedSHYpatterns;
var $loadedSHYdictionary;
var $SHYdictionary;
var $SHYdictionaryWords;

var $spanbgcolorarray;
var $default_font;
var $list_lineheight;
var $headerbuffer;
var $lastblocklevelchange;
var $nestedtablejustfinished;
var $linebreakjustfinished;
var $cell_border_dominance_L;
var $cell_border_dominance_R;
var $cell_border_dominance_T;
var $cell_border_dominance_B;
var $tbCSSlvl;
var $listCSSlvl;
var $table_keep_together;
var $plainCell_properties;
var $inherit_lineheight;
var $listitemtype;
var $shrin_k1;
var $outerfilled;

var $blockContext;
var $floatDivs;

var $tablecascadeCSS;
var $listcascadeCSS;

var $patterns;
var $pageBackgrounds;

var $bodyBackgroundGradient;
var $bodyBackgroundImage;
var $bodyBackgroundColor;

var $writingHTMLheader;	// internal flag - used both for writing HTMLHeaders/Footers and FixedPos block
var $writingHTMLfooter;
var $autoFontGroups;
var $angle;

var $gradients;

var $kwt_Reference;
var $kwt_BMoutlines;
var $kwt_toc;

var $tbrot_Reference;
var $tbrot_BMoutlines;
var $tbrot_toc;

var $col_Reference;
var $col_BMoutlines;
var $col_toc;

var $currentGraphId;
var $graphs;

var $floatbuffer;
var $floatmargins;

var $bullet;
var $bulletarray;

var $rtlAsArabicFarsi;		// DEPRACATED

var $currentLang;
var $default_lang;
var $default_available_fonts;
var $pageTemplate;
var $docTemplate;
var $docTemplateContinue;

var $arabGlyphs;
var $arabHex;
var $persianGlyphs;
var $persianHex;
var $arabVowels;
var $arabPrevLink;
var $arabNextLink;


var $formobjects; // array of Form Objects for WMF
var $InlineProperties;
var $InlineAnnots;
var $ktAnnots;
var $tbrot_Annots;
var $kwt_Annots;
var $columnAnnots;
var $columnForms;

var $PageAnnots;

var $pageDim;	// Keep track of page wxh for orientation changes - set in _beginpage, used in _putannots

var $breakpoints;

var $tableLevel;
var $tbctr;
var $innermostTableLevel;
var $saveTableCounter;
var $cellBorderBuffer;

var $saveHTMLFooter_height;
var $saveHTMLFooterE_height;

var $firstPageBoxHeader;
var $firstPageBoxHeaderEven;
var $firstPageBoxFooter;
var $firstPageBoxFooterEven;

var $page_box;
var $show_marks;	// crop or cross marks

var $basepathIsLocal;

var $use_kwt;
var $kwt;
var $kwt_height;
var $kwt_y0;
var $kwt_x0;
var $kwt_buffer;
var $kwt_Links;
var $kwt_moved;
var $kwt_saved;

var $PageNumSubstitutions;

var $table_borders_separate;
var $base_table_properties;
var $borderstyles;

var $listjustfinished;
var $blockjustfinished;

var $orig_bMargin;
var $orig_tMargin;
var $orig_lMargin;
var $orig_rMargin;
var $orig_hMargin;
var $orig_fMargin;

var $pageheaders;
var $pagefooters;

var $pageHTMLheaders;
var $pageHTMLfooters;

var $saveHTMLHeader;
var $saveHTMLFooter;

var $HTMLheaderPageLinks;
var $HTMLheaderPageAnnots;
var $HTMLheaderPageForms;

// See config_fonts.php for these next 5 values
var $available_unifonts;
var $sans_fonts;
var $serif_fonts;
var $mono_fonts;
var $defaultSubsFont;

// List of ALL available CJK fonts (incl. styles) (Adobe add-ons)  hw removed
var $available_CJK_fonts;

var $cascadeCSS;

var $HTMLHeader;
var $HTMLFooter;
var $HTMLHeaderE;
var $HTMLFooterE;
var $bufferoutput;

var $showdefaultpagenos;	// DEPRACATED -left for backward compatability


// CJK fonts
var $Big5_widths;
var $GB_widths;
var $SJIS_widths;
var $UHC_widths;

// SetProtection
var $encrypted;	//whether document is protected
var $Uvalue;	//U entry in pdf document
var $Ovalue;	//O entry in pdf document
var $Pvalue;	//P entry in pdf document
var $enc_obj_id;	//encryption object id
var $last_rc4_key;	//last RC4 key encrypted (cached for optimisation)
var $last_rc4_key_c;	//last RC4 computed key
var $encryption_key;
var $padding;	//used for encryption


// Bookmark
var $BMoutlines;
var $OutlineRoot;
// INDEX
var $ColActive;
var $Reference;
var $CurrCol;
var $NbCol;
var $y0;			//Top ordinate of columns
var $ColL;
var $ColWidth;
var $ColGap;
// COLUMNS
var $ColR;
var $ChangeColumn;
var $columnbuffer;
var $ColDetails;
var $columnLinks;
var $colvAlign;
// Substitutions
var $substitute;		// Array of substitution strings e.g. <ttz>112</ttz>
var $entsearch;		// Array of HTML entities (>ASCII 127) to substitute
var $entsubstitute;	// Array of substitution decimal unicode for the Hi entities


// Default values if no style sheet offered	(cf. http://www.w3.org/TR/CSS21/sample.html)
var $defaultCSS;

var $linemaxfontsize;
var $lineheight_correction;
var $lastoptionaltag;	// Save current block item which HTML specifies optionsl endtag
var $pageoutput;
var $charset_in;
var $blk;
var $blklvl;
var $ColumnAdjust;
var $ws;	// Word spacing
var $HREF;
var $pgwidth;
var $fontlist;
var $oldx;
var $oldy;
var $B;
var $U;      //underlining flag
var $S;	// SmallCaps flag
var $I;

var $tdbegin;
var $table;
var $cell;
var $col;
var $row;

var $divbegin;
var $divalign;
var $divwidth;
var $divheight;
var $divrevert;
var $spanbgcolor;

var $spanlvl;
var $listlvl;
var $listnum;
var $listtype;
var $listoccur;
var $listlist;
var $listitem;

var $pjustfinished;
var $ignorefollowingspaces;
var $SUP;
var $SUB;
var $SMALL;
var $BIG;
var $toupper;
var $tolower;
var $capitalize;
var $dash_on;
var $dotted_on;
var $strike;

var $CSS;
var $textbuffer;
var $currentfontstyle;
var $currentfontfamily;
var $currentfontsize;
var $colorarray;
var $bgcolorarray;
var $internallink;
var $enabledtags;

var $lineheight;
var $basepath;
var $outlineparam;
var $outline_on;

var $specialcontent;
var $selectoption;
var $objectbuffer;

// Table Rotation
var $table_rotate;
var $tbrot_maxw;
var $tbrot_maxh;
var $tablebuffer;
var $tbrot_align;
var $tbrot_Links;

var $divbuffer;		// Buffer used when keeping DIV on one page
var $keep_block_together;	// Keep a Block from page-break-inside: avoid
var $ktLinks;		// Keep-together Block links array
var $ktBlock;		// Keep-together Block array
var $ktForms;
var $ktReference;
var $ktBMoutlines;
var $_kttoc;

var $tbrot_y0;
var $tbrot_x0;
var $tbrot_w;
var $tbrot_h;

var $mb_enc;
var $directionality;

var $extgstates; // Used for alpha channel - Transparency (Watermark)
var $mgl;
var $mgt;
var $mgr;
var $mgb;

var $tts;
var $ttz;
var $tta;

var $headerDetails;
var $footerDetails;

// Best to alter the below variables using default stylesheet above
var $page_break_after_avoid;
var $margin_bottom_collapse;
var $list_indent;
var $list_align;
var $list_margin_bottom;
var $default_font_size;	// in pts
var $original_default_font_size;	// used to save default sizes when using table default
var $original_default_font;
var $watermark_font;
var $defaultAlign;

// TABLE
var $defaultTableAlign;
var $tablethead;
var $thead_font_weight;
var $thead_font_style;
var $thead_font_smCaps;
var $thead_valign_default;
var $thead_textalign_default;
var $tabletfoot;
var $tfoot_font_weight;
var $tfoot_font_style;
var $tfoot_font_smCaps;
var $tfoot_valign_default;
var $tfoot_textalign_default;

var $trow_text_rotate;

var $cellPaddingL;
var $cellPaddingR;
var $cellPaddingT;
var $cellPaddingB;
var $table_lineheight;
var $table_border_attr_set;
var $table_border_css_set;

var $shrin_k;			// factor with which to shrink tables - used internally - do not change
var $shrink_this_table_to_fit;	// 0 or false to disable; value (if set) gives maximum factor to reduce fontsize
var $MarginCorrection;	// corrects for OddEven Margins
var $margin_footer;
var $margin_header;

var $tabletheadjustfinished;
var $usingCoreFont;
var $charspacing;

//Private properties FROM FPDF
var $DisplayPreferences;
var $outlines;
var $flowingBlockAttr;
var $page;               //current page number
var $n;                  //current object number
var $offsets;            //array of object offsets
var $buffer;             //buffer holding in-memory PDF
var $pages;              //array containing pages
var $state;              //current document state
var $compress;           //compression flag
var $DefOrientation;     //default orientation
var $CurOrientation;     //current orientation
var $OrientationChanges; //array indicating orientation changes
var $k;                  //scale factor (number of points in user unit)
var $fwPt;
var $fhPt;         //dimensions of page format in points
var $fw;
var $fh;             //dimensions of page format in user unit
var $wPt;
var $hPt;           //current dimensions of page in points
var $w;
var $h;               //current dimensions of page in user unit
var $lMargin;            //left margin
var $tMargin;            //top margin
var $rMargin;            //right margin
var $bMargin;            //page break margin
var $cMarginL;            //cell margin Left
var $cMarginR;            //cell margin Right
var $cMarginT;            //cell margin Left
var $cMarginB;            //cell margin Right
var $DeflMargin;            //Default left margin
var $DefrMargin;            //Default right margin
var $x;
var $y;               //current position in user unit for cell positioning
var $lasth;              //height of last cell printed
var $LineWidth;          //line width in user unit
var $CoreFonts;          //array of standard font names
var $fonts;              //array of used fonts
var $FontFiles;          //array of font files
var $images;             //array of used images
var $PageLinks;          //array of links in pages
var $links;              //array of internal links
var $FontFamily;         //current font family
var $FontStyle;          //current font style
var $CurrentFont;        //current font info
var $FontSizePt;         //current font size in points
var $FontSize;           //current font size in user unit
var $DrawColor;          //commands for drawing color
var $FillColor;          //commands for filling color
var $TextColor;          //commands for text color
var $ColorFlag;          //indicates whether fill and text colors are different
var $autoPageBreak;      //automatic page breaking
var $PageBreakTrigger;   //threshold used to trigger page breaks
var $InFooter;           //flag set when processing footer
var $InHTMLFooter;

var $processingFooter;   //flag set when processing footer - added for columns
var $processingHeader;   //flag set when processing header - added for columns
var $ZoomMode;           //zoom display mode
var $LayoutMode;         //layout display mode
var $title;              //title
var $subject;            //subject
var $author;             //author
var $keywords;           //keywords
var $creator;            //creator

var $aliasNbPg;       //alias for total number of pages
var $aliasNbPgGp;       //alias for total number of pages in page group
var $aliasNbPgHex;
var $aliasNbPgGpHex;

var $ispre;

var $outerblocktags;
var $innerblocktags;


// **********************************
// **********************************
// **********************************
// **********************************
// **********************************
// **********************************
// **********************************
// **********************************
// **********************************

function __construct($mode='',$format='A4',$default_font_size=0,$default_font='',$mgl=15,$mgr=15,$mgt=16,$mgb=16,$mgh=9,$mgf=9, $orientation='P') {


	$this->time0 = microtime(true);
	//Some checks
	$this->_dochecks();

	// Set up Aliases for backwards compatability
	$this->UnvalidatedText =& $this->watermarkText;
	$this->TopicIsUnvalidated =& $this->showWatermarkText;
	$this->AliasNbPg =& $this->aliasNbPg;
	$this->AliasNbPgGp =& $this->aliasNbPgGp;
	$this->BiDirectional =& $this->biDirectional;
	$this->Anchor2Bookmark =& $this->anchor2Bookmark;
	$this->KeepColumns =& $this->keepColumns;
	$this->useOddEven =& $this->mirrorMargins;
	$this->useSubstitutionsMB =& $this->useSubstitutions;

	// mPDF 5.6.01
	$this->layers = array();
	$this->current_layer = 0;
	$this->open_layer_pane = false;

	$this->visibility='visible';

	//Initialization of properties
	$this->spotColors=array();
	$this->spotColorIDs = array();
	$this->tableBackgrounds = array();

	$this->kt_y00 = '';
	$this->kt_p00 = '';
	$this->iterationCounter = false;
	$this->BMPonly = array();
	$this->page=0;
	$this->n=2;
	$this->buffer='';
	$this->objectbuffer = array();
	$this->pages=array();
	$this->OrientationChanges=array();
	$this->state=0;
	$this->fonts=array();
	$this->FontFiles=array();
	$this->images=array();
	$this->links=array();
	$this->InFooter=false;
	$this->processingFooter=false;
	$this->processingHeader=false;
	$this->lasth=0;
	$this->FontFamily='';
	$this->FontStyle='';
	$this->FontSizePt=9;
	$this->U=false;
	// Small Caps
	$this->upperCase = array();
	$this->S = false;
	$this->smCapsScale = 1;
	$this->smCapsStretch = 100;
	$this->margBuffer = 0;	// mPDF 5.4.04
	$this->inMeter = false;	// mPDF 5.5.09

	$this->defTextColor = $this->TextColor = $this->SetTColor($this->ConvertColor(0),true);
	$this->defDrawColor = $this->DrawColor = $this->SetDColor($this->ConvertColor(0),true);
	$this->defFillColor = $this->FillColor = $this->SetFColor($this->ConvertColor(255),true);

	//SVG color names array
	//http://www.w3schools.com/css/css_colornames.asp
	$this->SVGcolors = array('antiquewhite'=>'#FAEBD7','aqua'=>'#00FFFF','aquamarine'=>'#7FFFD4','beige'=>'#F5F5DC','black'=>'#000000',
'blue'=>'#0000FF','brown'=>'#A52A2A','cadetblue'=>'#5F9EA0','chocolate'=>'#D2691E','cornflowerblue'=>'#6495ED','crimson'=>'#DC143C',
'darkblue'=>'#00008B','darkgoldenrod'=>'#B8860B','darkgreen'=>'#006400','darkmagenta'=>'#8B008B','darkorange'=>'#FF8C00',
'darkred'=>'#8B0000','darkseagreen'=>'#8FBC8F','darkslategray'=>'#2F4F4F','darkviolet'=>'#9400D3','deepskyblue'=>'#00BFFF',
'dodgerblue'=>'#1E90FF','firebrick'=>'#B22222','forestgreen'=>'#228B22','fuchsia'=>'#FF00FF','gainsboro'=>'#DCDCDC','gold'=>'#FFD700',
'gray'=>'#808080','green'=>'#008000','greenyellow'=>'#ADFF2F','hotpink'=>'#FF69B4','indigo'=>'#4B0082','khaki'=>'#F0E68C',
'lavenderblush'=>'#FFF0F5','lemonchiffon'=>'#FFFACD','lightcoral'=>'#F08080','lightgoldenrodyellow'=>'#FAFAD2','lightgreen'=>'#90EE90',
'lightsalmon'=>'#FFA07A','lightskyblue'=>'#87CEFA','lightslategray'=>'#778899','lightyellow'=>'#FFFFE0','lime'=>'#00FF00','limegreen'=>'#32CD32',
'magenta'=>'#FF00FF','maroon'=>'#800000','mediumaquamarine'=>'#66CDAA','mediumorchid'=>'#BA55D3','mediumseagreen'=>'#3CB371',
'mediumspringgreen'=>'#00FA9A','mediumvioletred'=>'#C71585','midnightblue'=>'#191970','mintcream'=>'#F5FFFA','moccasin'=>'#FFE4B5','navy'=>'#000080',
'olive'=>'#808000','orange'=>'#FFA500','orchid'=>'#DA70D6','palegreen'=>'#98FB98',
'palevioletred'=>'#D87093','peachpuff'=>'#FFDAB9','pink'=>'#FFC0CB','powderblue'=>'#B0E0E6','purple'=>'#800080',
'red'=>'#FF0000','royalblue'=>'#4169E1','salmon'=>'#FA8072','seagreen'=>'#2E8B57','sienna'=>'#A0522D','silver'=>'#C0C0C0','skyblue'=>'#87CEEB',
'slategray'=>'#708090','springgreen'=>'#00FF7F','steelblue'=>'#4682B4','tan'=>'#D2B48C','teal'=>'#008080','thistle'=>'#D8BFD8','turquoise'=>'#40E0D0',
'violetred'=>'#D02090','white'=>'#FFFFFF','yellow'=>'#FFFF00',
'aliceblue'=>'#f0f8ff', 'azure'=>'#f0ffff', 'bisque'=>'#ffe4c4', 'blanchedalmond'=>'#ffebcd', 'blueviolet'=>'#8a2be2', 'burlywood'=>'#deb887',
'chartreuse'=>'#7fff00', 'coral'=>'#ff7f50', 'cornsilk'=>'#fff8dc', 'cyan'=>'#00ffff', 'darkcyan'=>'#008b8b', 'darkgray'=>'#a9a9a9',
'darkgrey'=>'#a9a9a9', 'darkkhaki'=>'#bdb76b', 'darkolivegreen'=>'#556b2f', 'darkorchid'=>'#9932cc', 'darksalmon'=>'#e9967a',
'darkslateblue'=>'#483d8b', 'darkslategrey'=>'#2f4f4f', 'darkturquoise'=>'#00ced1', 'deeppink'=>'#ff1493', 'dimgray'=>'#696969',
'dimgrey'=>'#696969', 'floralwhite'=>'#fffaf0', 'ghostwhite'=>'#f8f8ff', 'goldenrod'=>'#daa520', 'grey'=>'#808080', 'honeydew'=>'#f0fff0',
'indianred'=>'#cd5c5c', 'ivory'=>'#fffff0', 'lavender'=>'#e6e6fa', 'lawngreen'=>'#7cfc00', 'lightblue'=>'#add8e6', 'lightcyan'=>'#e0ffff',
'lightgray'=>'#d3d3d3', 'lightgrey'=>'#d3d3d3', 'lightpink'=>'#ffb6c1', 'lightseagreen'=>'#20b2aa', 'lightslategrey'=>'#778899',
'lightsteelblue'=>'#b0c4de', 'linen'=>'#faf0e6', 'mediumblue'=>'#0000cd', 'mediumpurple'=>'#9370db', 'mediumslateblue'=>'#7b68ee',
'mediumturquoise'=>'#48d1cc', 'mistyrose'=>'#ffe4e1', 'navajowhite'=>'#ffdead', 'oldlace'=>'#fdf5e6', 'olivedrab'=>'#6b8e23', 'orangered'=>'#ff4500',
'palegoldenrod'=>'#eee8aa', 'paleturquoise'=>'#afeeee', 'papayawhip'=>'#ffefd5', 'peru'=>'#cd853f', 'plum'=>'#dda0dd', 'rosybrown'=>'#bc8f8f',
'saddlebrown'=>'#8b4513', 'sandybrown'=>'#f4a460', 'seashell'=>'#fff5ee', 'slateblue'=>'#6a5acd', 'slategrey'=>'#708090', 'snow'=>'#fffafa',
'tomato'=>'#ff6347', 'violet'=>'#ee82ee', 'wheat'=>'#f5deb3', 'whitesmoke'=>'#f5f5f5', 'yellowgreen'=>'#9acd32');

	$this->ColorFlag=false;
	$this->extgstates = array();

	$this->mb_enc='windows-1252';
	$this->directionality='ltr';
	$this->defaultAlign = 'L';
	$this->defaultTableAlign = 'L';

	$this->fixedPosBlockSave = array();
	$this->extraFontSubsets = 0;

	$this->SHYpatterns = array();
	$this->loadedSHYdictionary = false;
	$this->SHYdictionary = array();
	$this->SHYdictionaryWords = array();
	$this->blockContext = 1;
	$this->floatDivs = array();
	$this->DisplayPreferences='';

	$this->tablecascadeCSS = array();
	$this->listcascadeCSS = array();

	$this->patterns = array();		// Tiling patterns used for backgrounds
	$this->pageBackgrounds = array();
	$this->writingHTMLheader = false;	// internal flag - used both for writing HTMLHeaders/Footers and FixedPos block
	$this->writingHTMLfooter = false;	// internal flag - used both for writing HTMLHeaders/Footers and FixedPos block
	$this->gradients = array();

	$this->kwt_Reference = array();
	$this->kwt_BMoutlines = array();
	$this->kwt_toc = array();

	$this->tbrot_Reference = array();
	$this->tbrot_BMoutlines = array();
	$this->tbrot_toc = array();

	$this->col_Reference = array();
	$this->col_BMoutlines = array();
	$this->col_toc = array();
	$this->graphs = array();

	$this->pgsIns = array();
	$this->PDFAXwarnings = array();
	$this->inlineDisplayOff = false;
	$this->kerning = false;
	$this->lSpacingCSS = '';
	$this->wSpacingCSS = '';
	$this->fixedlSpacing = false;
	$this->minwSpacing = 0;


	$this->baselineC = 0.35;	// Baseline for text
	$this->noImageFile = str_replace("\\","/",dirname(__FILE__)) . '/includes/no_image.jpg';
	$this->subPos = 0;
	$this->forceExactLineheight = false;
	$this->listOcc = 0;
	$this->normalLineheight = 1.3;
	// These are intended as configuration variables, and should be set in config.php - which will override these values;
	// set here as failsafe as will cause an error if not defined
	$this->incrementFPR1 = 10;
	$this->incrementFPR2 = 10;
	$this->incrementFPR3 = 10;
	$this->incrementFPR4 = 10;

	$this->fullImageHeight = false;
	$this->floatbuffer = array();
	$this->floatmargins = array();
	$this->autoFontGroups = 0;
	$this->formobjects=array(); // array of Form Objects for WMF
	$this->InlineProperties=array();
	$this->InlineAnnots=array();
	$this->ktAnnots=array();
	$this->tbrot_Annots=array();
	$this->kwt_Annots=array();
	$this->columnAnnots=array();
	$this->pageDim=array();
	$this->breakpoints = array();	// used in columnbuffer
	$this->tableLevel=0;
	$this->tbctr=array();	// counter for nested tables at each level
	$this->page_box = array();
	$this->show_marks = '';	// crop or cross marks
	$this->kwt = false;
	$this->kwt_height = 0;
	$this->kwt_y0 = 0;
	$this->kwt_x0 = 0;
	$this->kwt_buffer = array();
	$this->kwt_Links = array();
	$this->kwt_moved = false;
	$this->kwt_saved = false;
	$this->PageNumSubstitutions = array();
	$this->base_table_properties=array();
	$this->borderstyles = array('inset','groove','outset','ridge','dotted','dashed','solid','double');
	$this->tbrot_align = 'C';
	$this->pageheaders=array();
	$this->pagefooters=array();

	$this->pageHTMLheaders=array();
	$this->pageHTMLfooters=array();
	$this->HTMLheaderPageLinks = array();
	$this->HTMLheaderPageAnnots = array();

	$this->ktForms = array();
	$this->HTMLheaderPageForms = array();
	$this->columnForms = array();
	$this->tbrotForms = array();
	$this->useRC128encryption = false;
	$this->uniqid = '';

	$this->cascadeCSS = array();
	$this->bufferoutput = false;
	$this->encrypted=false;    		//whether document is protected
	$this->BMoutlines=array();
	$this->ColActive=0;        		//Flag indicating that columns are on (the index is being processed)
	$this->Reference=array();  		//Array containing the references
	$this->CurrCol=0;              	//Current column number
	$this->ColL = array(0);			// Array of Left pos of columns - absolute - needs Margin correction for Odd-Even
	$this->ColR = array(0);			// Array of Right pos of columns - absolute pos - needs Margin correction for Odd-Even
	$this->ChangeColumn = 0;
	$this->columnbuffer = array();
	$this->ColDetails = array();		// Keeps track of some column details
	$this->columnLinks = array();		// Cross references PageLinks
	$this->substitute = array();		// Array of substitution strings e.g. <ttz>112</ttz>
	$this->entsearch = array();		// Array of HTML entities (>ASCII 127) to substitute
	$this->entsubstitute = array();	// Array of substitution decimal unicode for the Hi entities
	$this->lastoptionaltag = '';
	$this->charset_in = '';
	$this->blk = array();
	$this->blklvl = 0;
	$this->tts = false;
	$this->ttz = false;
	$this->tta = false;
	$this->ispre=false;

	$this->checkSIP = false;
	$this->checkSMP = false;
	$this->checkCJK = false;
	$this->tableCJK = false;

	$this->headerDetails=array();
	$this->footerDetails=array();
	$this->page_break_after_avoid = false;
	$this->margin_bottom_collapse = false;
	$this->tablethead = 0;
	$this->tabletfoot = 0;
	$this->table_border_attr_set = 0;
	$this->table_border_css_set = 0;
	$this->shrin_k = 1.0;
	$this->shrink_this_table_to_fit = 0;
	$this->MarginCorrection = 0;

	$this->tabletheadjustfinished = false;
	$this->usingCoreFont = false;
	$this->charspacing=0;

	$this->outlines=array();
	$this->autoPageBreak = true;

	require(_MPDF_PATH.'config.php');	// config data

	$this->_setPageSize($format, $orientation);
	$this->DefOrientation=$orientation;

	$this->margin_header=$mgh;
	$this->margin_footer=$mgf;

	$bmargin=$mgb;

	$this->DeflMargin = $mgl;
	$this->DefrMargin = $mgr;

	$this->orig_tMargin = $mgt;
	$this->orig_bMargin = $bmargin;
	$this->orig_lMargin = $this->DeflMargin;
	$this->orig_rMargin = $this->DefrMargin;
	$this->orig_hMargin = $this->margin_header;
	$this->orig_fMargin = $this->margin_footer;

	if ($this->setAutoTopMargin=='pad') { $mgt += $this->margin_header; }
	if ($this->setAutoBottomMargin=='pad') { $mgb += $this->margin_footer; }
	$this->SetMargins($this->DeflMargin,$this->DefrMargin,$mgt);	// sets l r t margin
	//Automatic page break
	$this->SetAutoPageBreak($this->autoPageBreak,$bmargin);	// sets $this->bMargin & PageBreakTrigger

	$this->pgwidth = $this->w - $this->lMargin - $this->rMargin;

	//Interior cell margin (1 mm) ? not used
	$this->cMarginL = 1;
	$this->cMarginR = 1;
	//Line width (0.2 mm)
	$this->LineWidth=.567/_MPDFK;

	//To make the function Footer() work - replaces {nb} with page number
	$this->AliasNbPages();
	$this->AliasNbPageGroups();

	$this->aliasNbPgHex = '{nbHEXmarker}';
	$this->aliasNbPgGpHex = '{nbpgHEXmarker}';

	//Enable all tags as default
	$this->DisableTags();
	//Full width display mode
	$this->SetDisplayMode(100);	// fullwidth?		'fullpage'
	//Compression
	$this->SetCompression(true);
	//Set default display preferences
	$this->SetDisplayPreferences('');

	// Font data
	require(_MPDF_PATH.'config_fonts.php');
	// Available fonts
	$this->available_unifonts = array();
	foreach ($this->fontdata AS $f => $fs) {
		if (isset($fs['R']) && $fs['R']) { $this->available_unifonts[] = $f; }
		if (isset($fs['B']) && $fs['B']) { $this->available_unifonts[] = $f.'B'; }
		if (isset($fs['I']) && $fs['I']) { $this->available_unifonts[] = $f.'I'; }
		if (isset($fs['BI']) && $fs['BI']) { $this->available_unifonts[] = $f.'BI'; }
	}

	$this->default_available_fonts = $this->available_unifonts;

	$optcore = false;
	$onlyCoreFonts = false;
	if (preg_match('/([\-+])aCJK/i',$mode, $m)) {
		preg_replace('/([\-+])aCJK/i','',$mode);
		if ($m[1]=='+') { $this->useAdobeCJK = true; }
		else { $this->useAdobeCJK = false; }
	}

	if (strlen($mode)==1) {
		if ($mode=='s') { $this->percentSubset = 100; $mode = ''; }
		else if ($mode=='c') { $onlyCoreFonts = true; $mode = ''; }
	}
	else if (substr($mode,-2)=='-s') {
		$this->percentSubset = 100;
		$mode = substr($mode,0,strlen($mode)-2);
	}
	else if (substr($mode,-2)=='-c') {
		$onlyCoreFonts = true;
		$mode = substr($mode,0,strlen($mode)-2);
	}
	else if (substr($mode,-2)=='-x') {
		$optcore = true;
		$mode = substr($mode,0,strlen($mode)-2);
	}

	// Autodetect if mode is a language_country string (en-GB or en_GB or en)
	if ((strlen($mode) == 5 && $mode != 'UTF-8') || strlen($mode) == 2) {
		list ($coreSuitable,$mpdf_pdf_unifonts) = GetLangOpts($mode, $this->useAdobeCJK);
		if ($coreSuitable && $optcore) { $onlyCoreFonts = true; }
		if ($mpdf_pdf_unifonts) {
			$this->RestrictUnicodeFonts($mpdf_pdf_unifonts);
			$this->default_available_fonts = $mpdf_pdf_unifonts;
		}
		$this->currentLang = $mode;
		$this->default_lang = $mode;
	}

	$this->onlyCoreFonts =  $onlyCoreFonts;

	if ($this->onlyCoreFonts) {
		$this->setMBencoding('windows-1252');	// sets $this->mb_enc
	}
	else {
		$this->setMBencoding('UTF-8');	// sets $this->mb_enc
	}
	@mb_regex_encoding('UTF-8');	// required only for mb_ereg... and mb_split functions


	// Adobe CJK fonts
	$this->available_CJK_fonts = array('gb','big5','sjis','uhc','gbB','big5B','sjisB','uhcB','gbI','big5I','sjisI','uhcI',
		'gbBI','big5BI','sjisBI','uhcBI');


	//Standard fonts
	$this->CoreFonts=array('ccourier'=>'Courier','ccourierB'=>'Courier-Bold','ccourierI'=>'Courier-Oblique','ccourierBI'=>'Courier-BoldOblique',
		'chelvetica'=>'Helvetica','chelveticaB'=>'Helvetica-Bold','chelveticaI'=>'Helvetica-Oblique','chelveticaBI'=>'Helvetica-BoldOblique',
		'ctimes'=>'Times-Roman','ctimesB'=>'Times-Bold','ctimesI'=>'Times-Italic','ctimesBI'=>'Times-BoldItalic',
		'csymbol'=>'Symbol','czapfdingbats'=>'ZapfDingbats');
	$this->fontlist=array("ctimes","ccourier","chelvetica","csymbol","czapfdingbats");

	// Substitutions
	$this->setHiEntitySubstitutions();

	if ($this->onlyCoreFonts) {
		$this->useSubstitutions = true;
		$this->SetSubstitutions();
	}
	else { $this->useSubstitutions = false; }

	if (file_exists(_MPDF_PATH.'mpdf.css')) {
		$css = file_get_contents(_MPDF_PATH.'mpdf.css');
		$css2 = $this->ReadDefaultCSS($css);
		$this->defaultCSS = $this->array_merge_recursive_unique($this->defaultCSS,$css2);
	}

	if ($default_font=='') {
	  if ($this->onlyCoreFonts) {
		if (in_array(strtolower($this->defaultCSS['BODY']['FONT-FAMILY']),$this->mono_fonts)) { $default_font = 'ccourier'; }
		else if (in_array(strtolower($this->defaultCSS['BODY']['FONT-FAMILY']),$this->sans_fonts)) { $default_font = 'chelvetica'; }
		else { $default_font = 'ctimes'; }
	  }
	  else { $default_font = $this->defaultCSS['BODY']['FONT-FAMILY']; }
	}
	if (!$default_font_size) {
		$mmsize = $this->ConvertSize($this->defaultCSS['BODY']['FONT-SIZE']);
		$default_font_size = $mmsize*(_MPDFK);
	}

	if ($default_font) { $this->SetDefaultFont($default_font); }
	if ($default_font_size) { $this->SetDefaultFontSize($default_font_size); }

	$this->SetLineHeight();	// lineheight is in mm

	$this->SetFColor($this->ConvertColor(255));
	$this->HREF='';
	$this->oldy=-1;
	$this->B=0;
	$this->U=false;
	$this->S=false;
	$this->I=0;

	$this->listlvl=0;
	$this->listnum=0;
	$this->listtype='';
	$this->listoccur=array();
	$this->listlist=array();
	$this->listitem=array();

	$this->tdbegin=false;
	$this->table=array();
	$this->cell=array();
	$this->col=-1;
	$this->row=-1;
	$this->cellBorderBuffer = array();

	$this->divbegin=false;
	$this->divalign='';
	$this->divwidth=0;
	$this->divheight=0;
	$this->spanbgcolor=false;
	$this->divrevert=false;
	$this->spanborder=false;
	$this->spanborddet=array();

	$this->blockjustfinished=false;
	$this->listjustfinished=false;
	$this->ignorefollowingspaces = true; //in order to eliminate exceeding left-side spaces
	$this->toupper=false;
	$this->tolower=false;
	$this->capitalize=false;
	$this->dash_on=false;
	$this->dotted_on=false;
	$this->SUP=false;
	$this->SUB=false;
	$this->strike=false;
	$this->textshadow='';

	$this->currentfontfamily='';
	$this->currentfontsize='';
	$this->currentfontstyle='';
	$this->colorarray=array();
	$this->spanbgcolorarray=array();
	$this->textbuffer=array();
	$this->CSS=array();
	$this->internallink=array();
	$this->basepath = "";

	$this->SetBasePath('');

	$this->outlineparam = array();
	$this->outline_on = false;

	$this->specialcontent = '';
	$this->selectoption = array();


}


function _setPageSize($format, &$orientation) {
	//Page format
	if(is_string($format))
	{
		if ($format=='') { $format = 'A4'; }
		$pfo = 'P';
		if(preg_match('/([0-9a-zA-Z]*)-L/i',$format,$m)) {	// e.g. A4-L = A4 landscape
			$format=$m[1];
			$pfo='L';
		}
		$format = $this->_getPageFormat($format);
		if (!$format) { $this->Error('Unknown page format: '.$format); }
		else { $orientation = $pfo; }

		$this->fwPt=$format[0];
		$this->fhPt=$format[1];
	}
	else
	{
		if (!$format[0] || !$format[1]) { $this->Error('Invalid page format: '.$format[0].' '.$format[1]); }
		$this->fwPt=$format[0]*_MPDFK;
		$this->fhPt=$format[1]*_MPDFK;
	}
	$this->fw=$this->fwPt/_MPDFK;
	$this->fh=$this->fhPt/_MPDFK;
	//Page orientation
	$orientation=strtolower($orientation);
	if($orientation=='p' or $orientation=='portrait')
	{
		$orientation='P';
		$this->wPt=$this->fwPt;
		$this->hPt=$this->fhPt;
	}
	elseif($orientation=='l' or $orientation=='landscape')
	{
		$orientation='L';
		$this->wPt=$this->fhPt;
		$this->hPt=$this->fwPt;
	}
	else $this->Error('Incorrect orientation: '.$orientation);
	$this->CurOrientation=$orientation;

	$this->w=$this->wPt/_MPDFK;
	$this->h=$this->hPt/_MPDFK;
}

function _getPageFormat($format) {
		switch (strtoupper($format)) {
			case '4A0': {$format = array(4767.87,6740.79); break;}
			case '2A0': {$format = array(3370.39,4767.87); break;}
			case 'A0': {$format = array(2383.94,3370.39); break;}
			case 'A1': {$format = array(1683.78,2383.94); break;}
			case 'A2': {$format = array(1190.55,1683.78); break;}
			case 'A3': {$format = array(841.89,1190.55); break;}
			case 'A4': default: {$format = array(595.28,841.89); break;}
			case 'A5': {$format = array(419.53,595.28); break;}
			case 'A6': {$format = array(297.64,419.53); break;}
			case 'A7': {$format = array(209.76,297.64); break;}
			case 'A8': {$format = array(147.40,209.76); break;}
			case 'A9': {$format = array(104.88,147.40); break;}
			case 'A10': {$format = array(73.70,104.88); break;}
			case 'B0': {$format = array(2834.65,4008.19); break;}
			case 'B1': {$format = array(2004.09,2834.65); break;}
			case 'B2': {$format = array(1417.32,2004.09); break;}
			case 'B3': {$format = array(1000.63,1417.32); break;}
			case 'B4': {$format = array(708.66,1000.63); break;}
			case 'B5': {$format = array(498.90,708.66); break;}
			case 'B6': {$format = array(354.33,498.90); break;}
			case 'B7': {$format = array(249.45,354.33); break;}
			case 'B8': {$format = array(175.75,249.45); break;}
			case 'B9': {$format = array(124.72,175.75); break;}
			case 'B10': {$format = array(87.87,124.72); break;}
			case 'C0': {$format = array(2599.37,3676.54); break;}
			case 'C1': {$format = array(1836.85,2599.37); break;}
			case 'C2': {$format = array(1298.27,1836.85); break;}
			case 'C3': {$format = array(918.43,1298.27); break;}
			case 'C4': {$format = array(649.13,918.43); break;}
			case 'C5': {$format = array(459.21,649.13); break;}
			case 'C6': {$format = array(323.15,459.21); break;}
			case 'C7': {$format = array(229.61,323.15); break;}
			case 'C8': {$format = array(161.57,229.61); break;}
			case 'C9': {$format = array(113.39,161.57); break;}
			case 'C10': {$format = array(79.37,113.39); break;}
			case 'RA0': {$format = array(2437.80,3458.27); break;}
			case 'RA1': {$format = array(1729.13,2437.80); break;}
			case 'RA2': {$format = array(1218.90,1729.13); break;}
			case 'RA3': {$format = array(864.57,1218.90); break;}
			case 'RA4': {$format = array(609.45,864.57); break;}
			case 'SRA0': {$format = array(2551.18,3628.35); break;}
			case 'SRA1': {$format = array(1814.17,2551.18); break;}
			case 'SRA2': {$format = array(1275.59,1814.17); break;}
			case 'SRA3': {$format = array(907.09,1275.59); break;}
			case 'SRA4': {$format = array(637.80,907.09); break;}
			case 'LETTER': {$format = array(612.00,792.00); break;}
			case 'LEGAL': {$format = array(612.00,1008.00); break;}
			case 'LEDGER': {$format = array(279.00,432.00); break;}
			case 'TABLOID': {$format = array(279.00,432.00); break;}
			case 'EXECUTIVE': {$format = array(521.86,756.00); break;}
			case 'FOLIO': {$format = array(612.00,936.00); break;}
			case 'B': {$format=array(362.83,561.26 );	 break;}		//	'B' format paperback size 128x198mm
			case 'A': {$format=array(314.65,504.57 );	 break;}		//	'A' format paperback size 111x178mm
			case 'DEMY': {$format=array(382.68,612.28 );  break;}		//	'Demy' format paperback size 135x216mm
			case 'ROYAL': {$format=array(433.70,663.30 );  break;}	//	'Royal' format paperback size 153x234mm
		}
	return $format;
}





function RestrictUnicodeFonts($res) {
	// $res = array of (Unicode) fonts to restrict to: e.g. norasi|norasiB - language specific
	if (count($res)) {	// Leave full list of available fonts if passed blank array
		$this->available_unifonts = $res;
	}
	else { $this->available_unifonts = $this->default_available_fonts; }
	if (count($this->available_unifonts) == 0) { $this->available_unifonts[] = $this->default_available_fonts[0]; }
	$this->available_unifonts = array_values($this->available_unifonts);
}


function setMBencoding($enc) {
	if ($this->mb_enc != $enc) {
		$this->mb_enc = $enc;
		mb_internal_encoding($this->mb_enc);
	}
}


function SetMargins($left,$right,$top) {
	//Set left, top and right margins
	$this->lMargin=$left;
	$this->rMargin=$right;
	$this->tMargin=$top;
}

function ResetMargins() {
	//ReSet left, top margins
	if (($this->forcePortraitHeaders || $this->forcePortraitMargins) && $this->DefOrientation=='P' && $this->CurOrientation=='L') {
	    if (($this->mirrorMargins) && (($this->page)%2==0)) {	// EVEN
		$this->tMargin=$this->orig_rMargin;
		$this->bMargin=$this->orig_lMargin;
	    }
	    else {	// ODD	// OR NOT MIRRORING MARGINS/FOOTERS
		$this->tMargin=$this->orig_lMargin;
		$this->bMargin=$this->orig_rMargin;
	    }
	   $this->lMargin=$this->DeflMargin;
	   $this->rMargin=$this->DefrMargin;
	   $this->MarginCorrection = 0;
	   $this->PageBreakTrigger=$this->h-$this->bMargin;
	}
	else  if (($this->mirrorMargins) && (($this->page)%2==0)) {	// EVEN
		$this->lMargin=$this->DefrMargin;
		$this->rMargin=$this->DeflMargin;
		$this->MarginCorrection = $this->DefrMargin-$this->DeflMargin;

	}
	else {	// ODD	// OR NOT MIRRORING MARGINS/FOOTERS
		$this->lMargin=$this->DeflMargin;
		$this->rMargin=$this->DefrMargin;
		if ($this->mirrorMargins) { $this->MarginCorrection = $this->DeflMargin-$this->DefrMargin; }
	}
	$this->x=$this->lMargin;

}

function SetLeftMargin($margin) {
	//Set left margin
	$this->lMargin=$margin;
	if($this->page>0 and $this->x<$margin) $this->x=$margin;
}

function SetTopMargin($margin) {
	//Set top margin
	$this->tMargin=$margin;
}

function SetRightMargin($margin) {
	//Set right margin
	$this->rMargin=$margin;
}

function SetAutoPageBreak($auto,$margin=0) {
	//Set auto page break mode and triggering margin
	$this->autoPageBreak=$auto;
	$this->bMargin=$margin;
	$this->PageBreakTrigger=$this->h-$margin;
}

function SetDisplayMode($zoom,$layout='continuous') {
	//Set display mode in viewer
	if($zoom=='fullpage' or $zoom=='fullwidth' or $zoom=='real' or $zoom=='default' or !is_string($zoom))
		$this->ZoomMode=$zoom;
	else
		$this->Error('Incorrect zoom display mode: '.$zoom);
	if($layout=='single' or $layout=='continuous' or $layout=='two' or $layout=='twoleft' or $layout=='tworight' or $layout=='default')
		$this->LayoutMode=$layout;
	else
		$this->Error('Incorrect layout display mode: '.$layout);
}

function SetCompression($compress) {
	//Set page compression
	if(function_exists('gzcompress'))	$this->compress=$compress;
	else $this->compress=false;
}

function SetTitle($title) {
	//Title of document // Arrives as UTF-8
	$this->title = $title;
}

function SetSubject($subject) {
	//Subject of document
	$this->subject= $subject;
}

function SetAuthor($author) {
	//Author of document
	$this->author= $author;
}

function SetKeywords($keywords) {
	//Keywords of document
	$this->keywords= $keywords;
}

function SetCreator($creator) {
	//Creator of document
	$this->creator= $creator;
}


function SetAnchor2Bookmark($x) {
	$this->anchor2Bookmark = $x;
}

function AliasNbPages($alias='{nb}') {
	//Define an alias for total number of pages
	$this->aliasNbPg=$alias;
}

function AliasNbPageGroups($alias='{nbpg}') {
	//Define an alias for total number of pages in a group
	$this->aliasNbPgGp=$alias;
}

function SetAlpha($alpha, $bm='Normal', $return=false, $mode='B') {
// alpha: real value from 0 (transparent) to 1 (opaque)
// bm:    blend mode, one of the following:
//          Normal, Multiply, Screen, Overlay, Darken, Lighten, ColorDodge, ColorBurn,
//          HardLight, SoftLight, Difference, Exclusion, Hue, Saturation, Color, Luminosity
// set alpha for stroking (CA) and non-stroking (ca) operations
// mode determines F (fill) S (stroke) B (both)
	if (($this->PDFA || $this->PDFX) && $alpha!=1) {
		if (($this->PDFA && !$this->PDFAauto) || ($this->PDFX && !$this->PDFXauto)) { $this->PDFAXwarnings[] = "Image opacity must be 100% (Opacity changed to 100%)"; }
		$alpha = 1;
	}
	$a = array('BM'=>'/'.$bm);
	if ($mode=='F' || $mode='B') $a['ca'] = $alpha;
	if ($mode=='S' || $mode='B') $a['CA'] = $alpha;
	$gs = $this->AddExtGState($a);
	if ($return) { return sprintf('/GS%d gs', $gs); }
	else { $this->_out(sprintf('/GS%d gs', $gs)); }
}

function AddExtGState($parms) {
	$n = count($this->extgstates);
	// check if graphics state already exists
	for ($i=1; $i<=$n; $i++) {
	  if (count($this->extgstates[$i]['parms']) == count($parms)) {
	    $same = true;
	    foreach($this->extgstates[$i]['parms'] AS $k=>$v) {
		if (!isset($parms[$k]) || $parms[$k] != $v) { $same = false; break; }
	    }
	    if ($same) { return $i; }
	  }
	}
	$n++;
	$this->extgstates[$n]['parms'] = $parms;
	return $n;
}

function SetVisibility($v) {
	if (($this->PDFA || $this->PDFX) && $this->visibility!='visible') { $this->PDFAXwarnings[] = "Cannot set visibility to anything other than full when using PDFA or PDFX"; return ''; }
	else if (!$this->PDFA && !$this->PDFX)
		$this->pdf_version='1.5';
	if($this->visibility!='visible') {
		$this->_out('EMC');
		$this->hasOC=intval($this->hasOC );	// mPDF 5.6.01
	}
	if($v=='printonly') {
		$this->_out('/OC /OC1 BDC');
		$this->hasOC=($this->hasOC | 1);	// mPDF 5.6.01
	}
	elseif($v=='screenonly') {
		$this->_out('/OC /OC2 BDC');
		$this->hasOC=($this->hasOC | 2);	// mPDF 5.6.01
	}
	elseif($v=='hidden') {
		$this->_out('/OC /OC3 BDC');
		$this->hasOC=($this->hasOC | 4);	// mPDF 5.6.01
	}
	elseif($v!='visible')
		$this->Error('Incorrect visibility: '.$v);
	$this->visibility=$v;
}

function Error($msg) {
	//Fatal error
	header('Content-Type: text/html; charset=utf-8');
	die('<B>mPDF error: </B>'.$msg);
}

function Open() {
	//Begin document
	if($this->state==0)	$this->_begindoc();
}

function Close() {
	//Terminate document
	if($this->state==3)	return;
	if($this->page==0) $this->AddPage($this->CurOrientation);
	if (count($this->cellBorderBuffer)) { $this->printcellbuffer(); }	// *TABLES*
	if ($this->tablebuffer) { $this->printtablebuffer(); }	// *TABLES*
	if (count($this->divbuffer)) { $this->printdivbuffer(); }

	// BODY Backgrounds
	$s = '';

	$s .= $this->PrintBodyBackgrounds();

	$s .= $this->PrintPageBackgrounds();
	$this->pages[$this->page] = preg_replace('/(___BACKGROUND___PATTERNS'.date('jY').')/', "\n".$s."\n".'\\1', $this->pages[$this->page]);
	$this->pageBackgrounds = array();

	if($this->visibility!='visible')
		$this->SetVisibility('visible');
	// mPDF 5.6.01 - LAYERS
	$this->EndLayer();

	if (!$this->tocontents || !$this->tocontents->TOCmark) { //Page footer
		$this->InFooter=true;
		$this->Footer();
		$this->InFooter=false;
	}

	//Close page
	$this->_endpage();

	//Close document
	$this->_enddoc();
}


function PrintBodyBackgrounds() {
	$s = '';
	$clx = 0;
	$cly = 0;
	$clw = $this->w;
	$clh = $this->h;
	// If using bleed and trim margins in paged media
	if ($this->pageDim[$this->page]['outer_width_LR'] || $this->pageDim[$this->page]['outer_width_TB']) {
		$clx = $this->pageDim[$this->page]['outer_width_LR'] - $this->pageDim[$this->page]['bleedMargin'];
		$cly = $this->pageDim[$this->page]['outer_width_TB'] - $this->pageDim[$this->page]['bleedMargin'];
		$clw = $this->w - 2*$clx;
		$clh = $this->h - 2*$cly;
	}

	if ($this->bodyBackgroundColor) {
		$s .= 'q ' .$this->SetFColor($this->bodyBackgroundColor, true)."\n";
		if ($this->bodyBackgroundColor{0}==5) {	// RGBa
			$s .= $this->SetAlpha(ord($this->bodyBackgroundColor{4})/100, 'Normal', true, 'F')."\n";
		}
		else if ($this->bodyBackgroundColor{0}==6) {	// CMYKa
			$s .= $this->SetAlpha(ord($this->bodyBackgroundColor{5})/100, 'Normal', true, 'F')."\n";
		}
		$s .= sprintf('%.3F %.3F %.3F %.3F re f Q', ($clx*_MPDFK), ($cly*_MPDFK),$clw*_MPDFK,$clh*_MPDFK)."\n";
	}

	return $s;
}


function PrintPageBackgrounds($adjustmenty=0) {
	$s = '';

	ksort($this->pageBackgrounds);
	foreach($this->pageBackgrounds AS $bl=>$pbs) {
		foreach ($pbs AS $pb) {
		  if ((!isset($pb['image_id']) && !isset($pb['gradient'])) || isset($pb['shadowonly'])) {	// Background colour or boxshadow
			// mPDF 5.6.01  - LAYERS
			if($pb['z-index']>0) {
				$this->current_layer = $pb['z-index'];
				$s .= "\n".'/OCBZ-index /ZI'.$pb['z-index'].' BDC'."\n";
			}

			if($pb['visibility']!='visible') {
				if($pb['visibility']=='printonly')
					$s .= '/OC /OC1 BDC'."\n";
				else if($pb['visibility']=='screenonly')
					$s .= '/OC /OC2 BDC'."\n";
				else if($pb['visibility']=='hidden')
					$s .= '/OC /OC3 BDC'."\n";
			}
			// Box shadow
			if (isset($pb['shadow']) && $pb['shadow']) { $s .= $pb['shadow']."\n"; }
			if (isset($pb['clippath']) && $pb['clippath']) { $s .= $pb['clippath']."\n"; }
			$s .= 'q '.$this->SetFColor($pb['col'], true)."\n";
			if ($pb['col']{0}==5) {	// RGBa
				$s .= $this->SetAlpha(ord($pb['col']{4})/100, 'Normal', true, 'F')."\n";
			}
			else if ($pb['col']{0}==6) {	// CMYKa
				$s .= $this->SetAlpha(ord($pb['col']{5})/100, 'Normal', true, 'F')."\n";
			}
			$s .= sprintf('%.3F %.3F %.3F %.3F re f Q',$pb['x']*_MPDFK,($this->h-$pb['y'])*_MPDFK,$pb['w']*_MPDFK,-$pb['h']*_MPDFK)."\n";
			if (isset($pb['clippath']) && $pb['clippath']) { $s .= 'Q'."\n"; }
			if($pb['visibility']!='visible')
				$s .= 'EMC'."\n";

			// mPDF 5.6.01  - LAYERS
			if($pb['z-index']>0) {
				$s .= "\n".'EMCBZ-index'."\n";
				$this->current_layer = 0;
			}
		  }
		}
	}
	return $s;
}

function PrintTableBackgrounds($adjustmenty=0) {
	$s = '';
	return $s;
}

// mPDF 5.6.01 - LAYERS
function BeginLayer($id) {
	if($this->current_layer>0) $this->EndLayer();
	if ($id < 1) { return false; }
	if (!isset($this->layers[$id])) {
		$this->layers[$id] = array('name'=>'Layer '.($id) );
		if (($this->PDFA || $this->PDFX)) { $this->PDFAXwarnings[] = "Cannot use layers when using PDFA or PDFX"; return ''; }
		else if (!$this->PDFA && !$this->PDFX) { $this->pdf_version='1.5'; }
	}
	$this->current_layer = $id;
	$this->_out('/OCZ-index /ZI'.$id.' BDC');

	$this->pageoutput[$this->page] = array();
}

function EndLayer() {
	if($this->current_layer>0) {
		$this->_out('EMCZ-index');
		$this->current_layer = 0;
	}
}



// Depracated - can use AddPage for all
function AddPages($orientation='',$condition='', $resetpagenum='', $pagenumstyle='', $suppress='',$mgl='',$mgr='',$mgt='',$mgb='',$mgh='',$mgf='',$ohname='',$ehname='',$ofname='',$efname='',$ohvalue=0,$ehvalue=0,$ofvalue=0,$efvalue=0,$pagesel='',$newformat='')
{
	$this->AddPage($orientation,$condition,$resetpagenum, $pagenumstyle, $suppress,$mgl,$mgr,$mgt,$mgb,$mgh,$mgf, $ohname, $ehname, $ofname, $efname, $ohvalue, $ehvalue, $ofvalue, $efvalue,$pagesel,$newformat);
}


function AddPageByArray($a) {
	if (!is_array($a)) { $a = array(); }
	$orientation = (isset($a['orientation']) ? $a['orientation'] : '');
	$condition = (isset($a['condition']) ? $a['condition'] : (isset($a['type']) ? $a['type'] : ''));
	$resetpagenum = (isset($a['resetpagenum']) ? $a['resetpagenum'] : '');
	$pagenumstyle = (isset($a['pagenumstyle']) ? $a['pagenumstyle'] : '');
	$suppress = (isset($a['suppress']) ? $a['suppress'] : '');
	$mgl = (isset($a['mgl']) ? $a['mgl'] : (isset($a['margin-left']) ? $a['margin-left'] : ''));
	$mgr = (isset($a['mgr']) ? $a['mgr'] : (isset($a['margin-right']) ? $a['margin-right'] : ''));
	$mgt = (isset($a['mgt']) ? $a['mgt'] : (isset($a['margin-top']) ? $a['margin-top'] : ''));
	$mgb = (isset($a['mgb']) ? $a['mgb'] : (isset($a['margin-bottom']) ? $a['margin-bottom'] : ''));
	$mgh = (isset($a['mgh']) ? $a['mgh'] : (isset($a['margin-header']) ? $a['margin-header'] : ''));
	$mgf = (isset($a['mgf']) ? $a['mgf'] : (isset($a['margin-footer']) ? $a['margin-footer'] : ''));
	$ohname = (isset($a['ohname']) ? $a['ohname'] : (isset($a['odd-header-name']) ? $a['odd-header-name'] : ''));
	$ehname = (isset($a['ehname']) ? $a['ehname'] : (isset($a['even-header-name']) ? $a['even-header-name'] : ''));
	$ofname = (isset($a['ofname']) ? $a['ofname'] : (isset($a['odd-footer-name']) ? $a['odd-footer-name'] : ''));
	$efname = (isset($a['efname']) ? $a['efname'] : (isset($a['even-footer-name']) ? $a['even-footer-name'] : ''));
	$ohvalue = (isset($a['ohvalue']) ? $a['ohvalue'] : (isset($a['odd-header-value']) ? $a['odd-header-value'] : 0));
	$ehvalue = (isset($a['ehvalue']) ? $a['ehvalue'] : (isset($a['even-header-value']) ? $a['even-header-value'] : 0));
	$ofvalue = (isset($a['ofvalue']) ? $a['ofvalue'] : (isset($a['odd-footer-value']) ? $a['odd-footer-value'] : 0));
	$efvalue = (isset($a['efvalue']) ? $a['efvalue'] : (isset($a['even-footer-value']) ? $a['even-footer-value'] : 0));
	$pagesel = (isset($a['pagesel']) ? $a['pagesel'] : (isset($a['pageselector']) ? $a['pageselector'] : ''));
	$newformat = (isset($a['newformat']) ? $a['newformat'] : (isset($a['sheet-size']) ? $a['sheet-size'] : ''));

	$this->AddPage($orientation,$condition,$resetpagenum, $pagenumstyle, $suppress,$mgl,$mgr,$mgt,$mgb,$mgh,$mgf, $ohname, $ehname, $ofname, $efname, $ohvalue, $ehvalue, $ofvalue, $efvalue,$pagesel,$newformat);

}




function AddPage($orientation='',$condition='', $resetpagenum='', $pagenumstyle='', $suppress='',$mgl='',$mgr='',$mgt='',$mgb='',$mgh='',$mgf='',$ohname='',$ehname='',$ofname='',$efname='',$ohvalue=0,$ehvalue=0,$ofvalue=0,$efvalue=0,$pagesel='',$newformat='')
{

	//Start a new page
	if($this->state==0) $this->Open();

	$bak_cml = $this->cMarginL;
	$bak_cmr = $this->cMarginR;
	$bak_dw = $this->divwidth;


	$bak_lh = $this->lineheight;

	$orientation = substr(strtoupper($orientation),0,1);
	$condition = strtoupper($condition);


	if ($condition == 'NEXT-EVEN') {	// always adds at least one new page to create an Even page
	   if (!$this->mirrorMargins) { $condition = ''; }
	   else {
		$this->AddPage($this->CurOrientation,'O');
		$condition = '';
	   }
	}
	if ($condition == 'NEXT-ODD') {	// always adds at least one new page to create an Odd page
	   if (!$this->mirrorMargins) { $condition = ''; }
	   else {
		$this->AddPage($this->CurOrientation,'E');
		$condition = '';
	   }
	}


	if ($condition == 'E') {	// only adds new page if needed to create an Even page
	   if (!$this->mirrorMargins || ($this->page)%2==0) { return false; }
	}
	if ($condition == 'O') {	// only adds new page if needed to create an Odd page
	   if (!$this->mirrorMargins || ($this->page)%2==1) { return false; }
	}

	if ($resetpagenum || $pagenumstyle || $suppress) {
		$this->PageNumSubstitutions[] = array('from'=>($this->page+1), 'reset'=> $resetpagenum, 'type'=>$pagenumstyle, 'suppress'=>$suppress);
	}


	$save_tr = $this->table_rotate;	// *TABLES*
	$this->table_rotate = 0;	// *TABLES*
	$save_kwt = $this->kwt;
	$this->kwt = 0;
	// mPDF 5.6.01 - LAYERS
	$save_layer = $this->current_layer;
	$save_vis = $this->visibility;

	if($this->visibility!='visible')
		$this->SetVisibility('visible');
	// mPDF 5.6.01 - LAYERS
	$this->EndLayer();

	// Paint Div Border if necessary
   	//PAINTS BACKGROUND COLOUR OR BORDERS for DIV - DISABLED FOR COLUMNS (cf. AcceptPageBreak) AT PRESENT in ->PaintDivBB
   	if (!$this->ColActive && $this->blklvl > 0) {
		if (isset($this->blk[$this->blklvl]['y0']) && $this->y == $this->blk[$this->blklvl]['y0']) {
			if (isset($this->blk[$this->blklvl]['startpage'])) { $this->blk[$this->blklvl]['startpage']++; }
			else { $this->blk[$this->blklvl]['startpage'] = 1; }
		}
		if ((isset($this->blk[$this->blklvl]['y0']) && $this->y > $this->blk[$this->blklvl]['y0']) || $this->flowingBlockAttr['is_table'] ) { $toplvl = $this->blklvl; }
		else { $toplvl = $this->blklvl-1; }
		$sy = $this->y;
		for ($bl=1;$bl<=$toplvl;$bl++) {

			// mPDF 5.6.01 - LAYERS
			if ($this->blk[$bl]['z-index']>0) {
				$this->BeginLayer($this->blk[$bl]['z-index']);
			}
			if (isset($this->blk[$bl]['visibility']) && $this->blk[$bl]['visibility'] && $this->blk[$bl]['visibility']!='visible') {
				$this->SetVisibility($this->blk[$bl]['visibility']);
			}

			$this->PaintDivBB('pagebottom',0,$bl);
		}
		$this->y = $sy;
		// RESET block y0 and x0 - see below
	}

	if($this->visibility!='visible')
		$this->SetVisibility('visible');
	// mPDF 5.6.01 - LAYERS
	$this->EndLayer();

	// BODY Backgrounds
	if ($this->page > 0) {
		$s = '';
		$s .= $this->PrintBodyBackgrounds();

		$s .= $this->PrintPageBackgrounds();
		$this->pages[$this->page] = preg_replace('/(___BACKGROUND___PATTERNS'.date('jY').')/', "\n".$s."\n".'\\1', $this->pages[$this->page]);
		$this->pageBackgrounds = array();
	}

	$save_kt = $this->keep_block_together;
	$this->keep_block_together = 0;

	$save_cols = false;


	$family=$this->FontFamily;
	$style=$this->FontStyle.($this->U ? 'U' : '').($this->S ? 'S' : '');
	$size=$this->FontSizePt;
	$this->ColumnAdjust = true;	// enables column height adjustment for the page
	$lw=$this->LineWidth;
	$dc=$this->DrawColor;
	$fc=$this->FillColor;
	$tc=$this->TextColor;
	$cf=$this->ColorFlag;
	if($this->page>0)
	{
		//Page footer
		$this->InFooter=true;

		$this->Reset();
		$this->pageoutput[$this->page] = array();

		$this->Footer();
		//Close page
		$this->_endpage();
	}


	//Start new page
	$this->_beginpage($orientation,$mgl,$mgr,$mgt,$mgb,$mgh,$mgf,$ohname,$ehname,$ofname,$efname,$ohvalue,$ehvalue,$ofvalue,$efvalue,$pagesel,$newformat);
	if ($this->docTemplate) {
		$pagecount = $this->SetSourceFile($this->docTemplate);
		if (($this->page - $this->docTemplateStart) > $pagecount) {
			if ($this->docTemplateContinue) {
				$tplIdx = $this->ImportPage($pagecount);
				$this->UseTemplate($tplIdx);
			}
		}
		else {
			$tplIdx = $this->ImportPage(($this->page - $this->docTemplateStart));
			$this->UseTemplate($tplIdx);
		}
	}
	if ($this->pageTemplate) {
		$this->UseTemplate($this->pageTemplate);
	}

	// Tiling Patterns
	$this->_out('___PAGE___START'.date('jY'));
	$this->_out('___BACKGROUND___PATTERNS'.date('jY'));
	$this->_out('___HEADER___MARKER'.date('jY'));
	$this->pageBackgrounds = array();

	//Set line cap style to square
	$this->SetLineCap(2);
	//Set line width
	$this->LineWidth=$lw;
	$this->_out(sprintf('%.3F w',$lw*_MPDFK));
	//Set font
	if($family)	$this->SetFont($family,$style,$size,true,true);	// forces write
	//Set colors
	$this->DrawColor=$dc;
	if($dc!=$this->defDrawColor) $this->_out($dc);
	$this->FillColor=$fc;
	if($fc!=$this->defFillColor) $this->_out($fc);
	$this->TextColor=$tc;
	$this->ColorFlag=$cf;

	//Page header
	$this->Header();

	//Restore line width
	if($this->LineWidth!=$lw)
	{
		$this->LineWidth=$lw;
		$this->_out(sprintf('%.3F w',$lw*_MPDFK));
	}
	//Restore font
	if($family)	$this->SetFont($family,$style,$size,true,true);	// forces write
	//Restore colors
	if($this->DrawColor!=$dc)
	{
		$this->DrawColor=$dc;
		$this->_out($dc);
	}
	if($this->FillColor!=$fc)
	{
		$this->FillColor=$fc;
		$this->_out($fc);
	}
	$this->TextColor=$tc;
	$this->ColorFlag=$cf;
 	$this->InFooter=false;

	// mPDF 5.6.01 - LAYERS
	if ($save_layer>0)
		$this->BeginLayer($save_layer);

	if($save_vis!='visible')
		$this->SetVisibility($save_vis);



   	//RESET BLOCK BORDER TOP
   	if (!$this->ColActive) {
		for($bl=1;$bl<=$this->blklvl;$bl++) {
			$this->blk[$bl]['y0'] = $this->y;
			if (isset($this->blk[$bl]['x0'])) { $this->blk[$bl]['x0'] += $this->MarginCorrection; }
			else { $this->blk[$bl]['x0'] = $this->MarginCorrection; }
			// Added mPDF 3.0 Float DIV
			$this->blk[$bl]['marginCorrected'][$this->page] = true;
		}
	}


	$this->table_rotate = $save_tr;	// *TABLES*
	$this->kwt = $save_kwt;

	$this->keep_block_together = $save_kt ;

	$this->cMarginL = $bak_cml;
	$this->cMarginR = $bak_cmr;
	$this->divwidth = $bak_dw;

	$this->lineheight = $bak_lh;
}


function PageNo() {
	//Get current page number
	return $this->page;
}

function AddSpotColorsFromFile($file) {
	$colors = @file($file) or die("Cannot load spot colors file - ".$file);
	foreach($colors AS $sc) {
		list($name, $c, $m, $y, $k) = preg_split("/\t/",$sc);
		$c = intval($c);
		$m = intval($m);
		$y = intval($y);
		$k = intval($k);
		$this->AddSpotColor($name, $c, $m, $y, $k);
	}
}

function AddSpotColor($name, $c, $m, $y, $k) {
	$name = strtoupper(trim($name));
	if(!isset($this->spotColors[$name])) {
		$i=count($this->spotColors)+1;
		$this->spotColors[$name]=array('i'=>$i,'c'=>$c,'m'=>$m,'y'=>$y,'k'=>$k);
		$this->spotColorIDs[$i]=$name;
	}
}

function SetColor($col, $type='') {
	$out = '';
	if ($col{0}==3 || $col{0}==5) {	// RGB / RGBa
		$out = sprintf('%.3F %.3F %.3F rg',ord($col{1})/255,ord($col{2})/255,ord($col{3})/255);
	}
	else if ($col{0}==1) {	// GRAYSCALE
		$out = sprintf('%.3F g',ord($col{1})/255);
	}
	else if ($col{0}==2) {	// SPOT COLOR
		$out = sprintf('/CS%d cs %.3F scn',ord($col{1}),ord($col{2})/100);
	}
	else if ($col{0}==4 || $col{0}==6) {	// CMYK / CMYKa
		$out = sprintf('%.3F %.3F %.3F %.3F k', ord($col{1})/100, ord($col{2})/100, ord($col{3})/100, ord($col{4})/100);
	}
	if ($type=='Draw') { $out = strtoupper($out); }	// e.g. rg => RG
	else if ($type=='CodeOnly') { $out = preg_replace('/\s(rg|g|k)/','',$out); }
	return $out;
}


function SetDColor($col, $return=false) {
	$out = $this->SetColor($col, 'Draw');
	if ($return) { return $out; }
	if ($out=='') { return ''; }
	$this->DrawColor = $out;
	if($this->page>0 && ((isset($this->pageoutput[$this->page]['DrawColor']) && $this->pageoutput[$this->page]['DrawColor'] != $this->DrawColor) || !isset($this->pageoutput[$this->page]['DrawColor']) || $this->keep_block_together)) { $this->_out($this->DrawColor); }
	$this->pageoutput[$this->page]['DrawColor'] = $this->DrawColor;
}

function SetFColor($col, $return=false) {
	$out = $this->SetColor($col, 'Fill');
	if ($return) { return $out; }
	if ($out=='') { return ''; }
	$this->FillColor = $out;
	$this->ColorFlag = ($out != $this->TextColor);
	if($this->page>0 && ((isset($this->pageoutput[$this->page]['FillColor']) && $this->pageoutput[$this->page]['FillColor'] != $this->FillColor) || !isset($this->pageoutput[$this->page]['FillColor']) || $this->keep_block_together)) { $this->_out($this->FillColor); }
	$this->pageoutput[$this->page]['FillColor'] = $this->FillColor;
}

function SetTColor($col, $return=false) {
	$out = $this->SetColor($col, 'Text');
	if ($return) { return $out; }
	if ($out=='') { return ''; }
	$this->TextColor = $out;
	$this->ColorFlag = ($this->FillColor != $out);
}


function SetDrawColor($r,$g=-1,$b=-1,$col4=-1, $return=false) {
	//Set color for all stroking operations
	$col = array();
	if(($r==0 and $g==0 and $b==0 && $col4 == -1) or $g==-1) { $col = $this->ConvertColor($r); }
	else if ($col4 == -1) { $col = $this->ConvertColor('rgb('.$r.','.$g.','.$b.')'); }
	else { $col = $this->ConvertColor('cmyk('.$r.','.$g.','.$b.','.$col4.')'); }
	$out = $this->SetDColor($col, $return);
	return $out;
}

function SetFillColor($r,$g=-1,$b=-1,$col4=-1, $return=false) {
	//Set color for all filling operations
	$col = array();
	if(($r==0 and $g==0 and $b==0 && $col4 == -1) or $g==-1) { $col = $this->ConvertColor($r); }
	else if ($col4 == -1) { $col = $this->ConvertColor('rgb('.$r.','.$g.','.$b.')'); }
	else { $col = $this->ConvertColor('cmyk('.$r.','.$g.','.$b.','.$col4.')'); }
	$out = $this->SetFColor($col, $return);
	return $out;
}

function SetTextColor($r,$g=-1,$b=-1,$col4=-1, $return=false) {
	//Set color for text
	$col = array();
	if(($r==0 and $g==0 and $b==0 && $col4 == -1) or $g==-1) { $col = $this->ConvertColor($r); }
	else if ($col4 == -1) { $col = $this->ConvertColor('rgb('.$r.','.$g.','.$b.')'); }
	else { $col = $this->ConvertColor('cmyk('.$r.','.$g.','.$b.','.$col4.')'); }
	$out = $this->SetTColor($col, $return);
	return $out;
}

function _getCharWidth(&$cw, $u, $isdef=true) {
	if ($u==0) { $w = false; }
	else { $w = (ord($cw[$u*2]) << 8) + ord($cw[$u*2+1]); }
	if ($w == 65535) { return 0; }
	else if ($w) { return $w; }
	else if ($isdef) { return false; }
	else { return 0; }
}

function _charDefined(&$cw, $u) {
	if ($u==0) { return false; }
	$w = (ord($cw[$u*2]) << 8) + ord($cw[$u*2+1]);
	if ($w) { return true; }
	else { return false; }
}

function GetCharWidthCore($c) {
	//Get width of a single character in the current Core font
	$c = (string)$c;
	$w = 0;
	// Soft Hyphens chr(173)
	if ($c == chr(173) && $this->FontFamily!='csymbol' && $this->FontFamily!='czapfdingbats') {
		return 0;
	}
	else if ($this->S && isset($this->upperCase[ord($c)])) {
		$charw = $this->CurrentFont['cw'][chr($this->upperCase[ord($c)])];
		if ($charw!==false) {
			$charw = $charw*$this->smCapsScale * $this->smCapsStretch/100;
			$w+=$charw;
		}
	}
	else if (isset($this->CurrentFont['cw'][$c])) {
		$w += $this->CurrentFont['cw'][$c];
	}
	else if (isset($this->CurrentFont['cw'][ord($c)])) {
		$w += $this->CurrentFont['cw'][ord($c)];
	}
	$w *=  ($this->FontSize/ 1000);
	if ($this->minwSpacing || $this->fixedlSpacing) {
		if ($c==' ') $nb_spaces = 1;
		else $nb_spaces = 0;
		$w += $this->fixedlSpacing + ($nb_spaces * $this->minwSpacing);
	}
	return ($w);
}

function GetCharWidthNonCore($c, $addSubset=true) {
	//Get width of a single character in the current Non-Core font
	$c = (string)$c;
	$w = 0;
	$unicode = $this->UTF8StringToArray($c, $addSubset);
	$char = $unicode[0];
			if ($char == 173) { return 0; }	// Soft Hyphens
			else if ($this->S && isset($this->upperCase[$char])) {
				$charw = $this->_getCharWidth($this->CurrentFont['cw'],$this->upperCase[$char]);
				if ($charw!==false) {
					$charw = $charw*$this->smCapsScale * $this->smCapsStretch/100;
					$w+=$charw;
				}
				elseif(isset($this->CurrentFont['desc']['MissingWidth'])) { $w += $this->CurrentFont['desc']['MissingWidth']; }
				elseif(isset($this->CurrentFont['MissingWidth'])) { $w += $this->CurrentFont['MissingWidth']; }
				else { $w += 500; }
			}
			else {
				$charw = $this->_getCharWidth($this->CurrentFont['cw'],$char);
				if ($charw!==false) { $w+=$charw; }
				elseif(isset($this->CurrentFont['desc']['MissingWidth'])) { $w += $this->CurrentFont['desc']['MissingWidth']; }
				elseif(isset($this->CurrentFont['MissingWidth'])) { $w += $this->CurrentFont['MissingWidth']; }
				else { $w += 500; }
			}
	$w *=  ($this->FontSize/ 1000);
	if ($this->minwSpacing || $this->fixedlSpacing) {
		if ($c==' ') $nb_spaces = 1;
		else $nb_spaces = 0;
		$w += $this->fixedlSpacing + ($nb_spaces * $this->minwSpacing);
	}
	return ($w);
}


function GetCharWidth($c, $addSubset=true) {
	if (!$this->usingCoreFont) {
		return $this->GetCharWidthNonCore($c, $addSubset);
	}
	else {
		return $this->GetCharWidthCore($c);
	}
}

function GetStringWidth($s, $addSubset=true) {
			//Get width of a string in the current font
			$s = (string)$s;
			$cw = &$this->CurrentFont['cw'];
			$w = 0;
			$kerning = 0;
			$lastchar = 0;
			$nb_carac = 0;
			$nb_spaces = 0;
			// mPDF ITERATION
			if ($this->iterationCounter) $s = preg_replace('/{iteration ([a-zA-Z0-9_]+)}/', '\\1', $s);

			if (!$this->usingCoreFont) {
			      $s = str_replace("\xc2\xad",'',$s );
				$unicode = $this->UTF8StringToArray($s, $addSubset);
				if ($this->minwSpacing || $this->fixedlSpacing) {
					$nb_carac = count($unicode);
					$nb_spaces = mb_substr_count($s,' ', $this->mb_enc);
				}
					foreach($unicode as $char) {
						if ($this->S && isset($this->upperCase[$char])) {
							$charw = $this->_getCharWidth($cw,$this->upperCase[$char]);
							if ($charw!==false) {
								$charw = $charw*$this->smCapsScale * $this->smCapsStretch/100;
								$w+=$charw;
							}
							elseif(isset($this->CurrentFont['desc']['MissingWidth'])) { $w += $this->CurrentFont['desc']['MissingWidth']; }
							elseif(isset($this->CurrentFont['MissingWidth'])) { $w += $this->CurrentFont['MissingWidth']; }
							else { $w += 500; }
						}
						else {
							$charw = $this->_getCharWidth($cw,$char);
							if ($charw!==false) { $w+=$charw; }
							elseif(isset($this->CurrentFont['desc']['MissingWidth'])) { $w += $this->CurrentFont['desc']['MissingWidth']; }
							elseif(isset($this->CurrentFont['MissingWidth'])) { $w += $this->CurrentFont['MissingWidth']; }
							else { $w += 500; }
							if ($this->kerning && $this->useKerning && $lastchar) {
								if (isset($this->CurrentFont['kerninfo'][$lastchar][$char])) {
									$kerning += $this->CurrentFont['kerninfo'][$lastchar][$char];
								}
							}
							$lastchar = $char;
						}
					}

			}
			else {
				if ($this->FontFamily!='csymbol' && $this->FontFamily!='czapfdingbats') {
			      	$s = str_replace(chr(173),'',$s );
				}
				$nb_carac = $l = strlen($s);
				if ($this->minwSpacing || $this->fixedlSpacing) {
					$nb_spaces = substr_count($s,' ');
				}
				for($i=0; $i<$l; $i++) {
					if ($this->S && isset($this->upperCase[ord($s[$i])])) {
						$charw = $cw[chr($this->upperCase[ord($s[$i])])];
						if ($charw!==false) {
							$charw = $charw*$this->smCapsScale * $this->smCapsStretch/100;
							$w+=$charw;
						}
					}
					else if (isset($cw[$s[$i]])) {
						$w += $cw[$s[$i]];
					}
					else if (isset($cw[ord($s[$i])])) {
						$w += $cw[ord($s[$i])];
					}
					if ($this->kerning && $this->useKerning && $i>0) {
						if (isset($this->CurrentFont['kerninfo'][$s[($i-1)]][$s[$i]])) {
							$kerning += $this->CurrentFont['kerninfo'][$s[($i-1)]][$s[$i]];
						}
					}
				}
			}
			unset($cw);
			if ($this->kerning && $this->useKerning) { $w += $kerning; }
			$w *=  ($this->FontSize/ 1000);
			$w += (($nb_carac + $nb_spaces) * $this->fixedlSpacing) + ($nb_spaces * $this->minwSpacing);
			return ($w);
}

function SetLineWidth($width) {
	//Set line width
	$this->LineWidth=$width;
	$lwout = (sprintf('%.3F w',$width*_MPDFK));
	if($this->page>0 && ((isset($this->pageoutput[$this->page]['LineWidth']) && $this->pageoutput[$this->page]['LineWidth'] != $lwout) || !isset($this->pageoutput[$this->page]['LineWidth']) || $this->keep_block_together)) {
		 $this->_out($lwout);
	}
	$this->pageoutput[$this->page]['LineWidth'] = $lwout;
}

function Line($x1,$y1,$x2,$y2) {
	//Draw a line
	$this->_out(sprintf('%.3F %.3F m %.3F %.3F l S',$x1*_MPDFK,($this->h-$y1)*_MPDFK,$x2*_MPDFK,($this->h-$y2)*_MPDFK));
}

function Arrow($x1,$y1,$x2,$y2,$headsize=3,$fill='B',$angle=25) {
  //F == fill //S == stroke //B == stroke and fill
  // angle = splay of arrowhead - 1 - 89 degrees
  if($fill=='F')	$fill='f';
  elseif($fill=='FD' or $fill=='DF' or $fill=='B') $fill='B';
  else $fill='S';
  $a = atan2(($y2-$y1),($x2-$x1));
  $b = $a + deg2rad($angle);
  $c = $a - deg2rad($angle);
  $x3 = $x2 - ($headsize* cos($b));
  $y3 = $this->h-($y2 - ($headsize* sin($b)));
  $x4 = $x2 - ($headsize* cos($c));
  $y4 = $this->h-($y2 - ($headsize* sin($c)));

  $x5 = $x3-($x3-$x4)/2;	// mid point of base of arrowhead - to join arrow line to
  $y5 = $y3-($y3-$y4)/2;

  $s = '';
  $s.=sprintf('%.3F %.3F m %.3F %.3F l S',$x1*_MPDFK,($this->h-$y1)*_MPDFK,$x5*_MPDFK,$y5*_MPDFK);
  $this->_out($s);

  $s = '';
  $s.=sprintf('%.3F %.3F m %.3F %.3F l %.3F %.3F l %.3F %.3F l %.3F %.3F l ',$x5*_MPDFK,$y5*_MPDFK,$x3*_MPDFK,$y3*_MPDFK,$x2*_MPDFK,($this->h-$y2)*_MPDFK,$x4*_MPDFK,$y4*_MPDFK,$x5*_MPDFK,$y5*_MPDFK);
  $s.=$fill;
  $this->_out($s);
}


function Rect($x,$y,$w,$h,$style='') {
	//Draw a rectangle
	if($style=='F')	$op='f';
	elseif($style=='FD' or $style=='DF') $op='B';
	else $op='S';
	$this->_out(sprintf('%.3F %.3F %.3F %.3F re %s',$x*_MPDFK,($this->h-$y)*_MPDFK,$w*_MPDFK,-$h*_MPDFK,$op));
}

function AddFont($family,$style='') {
	if(empty($family)) { return; }
	$family = strtolower($family);
	$style=strtoupper($style);
	$style=str_replace('U','',$style);
	if($style=='IB') $style='BI';
	$fontkey = $family.$style;
	// check if the font has been already added
	if(isset($this->fonts[$fontkey])) {
		return;
	}


	if ($this->usingCoreFont) { die("mPDF Error - problem with Font management"); }

	$stylekey = $style;
	if (!$style) { $stylekey = 'R'; }

	if (!isset($this->fontdata[$family][$stylekey]) || !$this->fontdata[$family][$stylekey]) {
		die('mPDF Error - Font is not supported - '.$family.' '.$style);
	}

	$name = '';
	$originalsize = 0;
	$sip = false;
	$smp = false;
	$unAGlyphs = false;	// mPDF 5.4.05
	$haskerninfo = false;
	$BMPselected = false;
	@include(_MPDF_TTFONTDATAPATH.$fontkey.'.mtx.php');

	$ttffile = '';
	if (defined('_MPDF_SYSTEM_TTFONTS')) {
		$ttffile = _MPDF_SYSTEM_TTFONTS.$this->fontdata[$family][$stylekey];
		if (!file_exists($ttffile)) { $ttffile = ''; }
	}
	if (!$ttffile) {
		$ttffile = _MPDF_TTFONTPATH.$this->fontdata[$family][$stylekey];
		if (!file_exists($ttffile)) {
			/*
			 * Try load in the font file from the PDF_TEMPLATE
			 */
			global $gfpdfe_data;
			$ttffile = $gfpdfe_data->template_site_location . 'fonts/' . $this->fontdata[$family][$stylekey];
			if(!file_exists($ttffile))
			{
				die("mPDF Error - cannot find TTF TrueType font file - ".$ttffile);
			}
		}
	}
	$ttfstat = stat($ttffile);

	if (isset($this->fontdata[$family]['TTCfontID'][$stylekey])) { $TTCfontID = $this->fontdata[$family]['TTCfontID'][$stylekey]; }
	else { $TTCfontID = 0; }


	$BMPonly = false;
	if (in_array($family,$this->BMPonly)) { $BMPonly = true; }
	$regenerate = false;
	if ($BMPonly && !$BMPselected) { $regenerate = true; }
	else if (!$BMPonly && $BMPselected) { $regenerate = true; }
	if ($this->useKerning && !$haskerninfo) { $regenerate = true; }
	// mPDF 5.4.05
	if (isset($this->fontdata[$family]['unAGlyphs']) && $this->fontdata[$family]['unAGlyphs'] && !$unAGlyphs) {
		$regenerate = true;
		$unAGlyphs = true;
	}
	else if ((!isset($this->fontdata[$family]['unAGlyphs']) || !$this->fontdata[$family]['unAGlyphs']) && $unAGlyphs) {
		$regenerate = true;
		$unAGlyphs = false;
	}
	if (!isset($name) || $originalsize != $ttfstat['size'] || $regenerate) {
		if (!class_exists('TTFontFile', false)) { include(_MPDF_PATH .'classes/ttfontsuni.php'); }
		$ttf = new TTFontFile();
		$ttf->getMetrics($ttffile, $TTCfontID, $this->debugfonts, $BMPonly, $this->useKerning, $unAGlyphs);	// mPDF 5.4.05
		$cw = $ttf->charWidths;
		$kerninfo = $ttf->kerninfo;
		$haskerninfo = true;
		$name = preg_replace('/[ ()]/','',$ttf->fullName);
		$sip = $ttf->sipset;
		$smp = $ttf->smpset;

		$desc= array('Ascent'=>round($ttf->ascent),
		'Descent'=>round($ttf->descent),
		'CapHeight'=>round($ttf->capHeight),
		'Flags'=>$ttf->flags,
		'FontBBox'=>'['.round($ttf->bbox[0])." ".round($ttf->bbox[1])." ".round($ttf->bbox[2])." ".round($ttf->bbox[3]).']',
		'ItalicAngle'=>$ttf->italicAngle,
		'StemV'=>round($ttf->stemV),
		'MissingWidth'=>round($ttf->defaultWidth));
		$panose = '';
		// mPDF 5.5.19
		if (count($ttf->panose)) {
			$panoseArray = array_merge(array($ttf->sFamilyClass, $ttf->sFamilySubClass), $ttf->panose);
			foreach($panoseArray as $value)
				$panose .= ' '.dechex($value);
		}
		$up = round($ttf->underlinePosition);
		$ut = round($ttf->underlineThickness);
		$originalsize = $ttfstat['size']+0;
		$type = 'TTF';
		//Generate metrics .php file
		$s='<?php'."\n";
		$s.='$name=\''.$name."';\n";
		$s.='$type=\''.$type."';\n";
		$s.='$desc='.var_export($desc,true).";\n";
		$s.='$up='.$up.";\n";
		$s.='$ut='.$ut.";\n";
		$s.='$ttffile=\''.$ttffile."';\n";
		$s.='$TTCfontID=\''.$TTCfontID."';\n";
		$s.='$originalsize='.$originalsize.";\n";
		if ($sip) $s.='$sip=true;'."\n";
		else $s.='$sip=false;'."\n";
		if ($smp) $s.='$smp=true;'."\n";
		else $s.='$smp=false;'."\n";
		if ($BMPonly) $s.='$BMPselected=true;'."\n";
		else $s.='$BMPselected=false;'."\n";
		$s.='$fontkey=\''.$fontkey."';\n";
		$s.='$panose=\''.$panose."';\n";
		if ($this->useKerning) {
			$s.='$kerninfo='.var_export($kerninfo,true).";\n";
			$s.='$haskerninfo=true;'."\n";
		}
		else $s.='$haskerninfo=false;'."\n";
		// mPDF 5.4.05
		if ($this->fontdata[$family]['unAGlyphs']) {
			$s.='$unAGlyphs=true;'."\n";
		}
		else $s.='$unAGlyphs=false;'."\n";
		$s.="?>";
		if (is_writable(dirname(_MPDF_TTFONTDATAPATH.'x'))) {
			$fh = fopen(_MPDF_TTFONTDATAPATH.$fontkey.'.mtx.php',"w");
			fwrite($fh,$s,strlen($s));
			fclose($fh);
			$fh = fopen(_MPDF_TTFONTDATAPATH.$fontkey.'.cw.dat',"wb");
			fwrite($fh,$cw,strlen($cw));
			fclose($fh);
			@unlink(_MPDF_TTFONTDATAPATH.$fontkey.'.cgm');
			@unlink(_MPDF_TTFONTDATAPATH.$fontkey.'.z');
			@unlink(_MPDF_TTFONTDATAPATH.$fontkey.'.cw127.php');
			@unlink(_MPDF_TTFONTDATAPATH.$fontkey.'.cw');
		}
		else if ($this->debugfonts) { $this->Error('Cannot write to the font caching directory - '._MPDF_TTFONTDATAPATH); }
		unset($ttf);
	}
	else {
		$cw = @file_get_contents(_MPDF_TTFONTDATAPATH.$fontkey.'.cw.dat');
	}

	if (isset($this->fontdata[$family]['indic']) && $this->fontdata[$family]['indic']) { $indic = true; }
	else { $indic = false; }
	if (isset($this->fontdata[$family]['sip-ext']) && $this->fontdata[$family]['sip-ext']) { $sipext = $this->fontdata[$family]['sip-ext']; }
	else { $sipext = ''; }


	$i = count($this->fonts)+$this->extraFontSubsets+1;
	if ($sip || $smp) {
		$this->fonts[$fontkey] = array('i'=>$i, 'type'=>$type, 'name'=>$name, 'desc'=>$desc, 'panose'=>$panose, 'up'=>$up, 'ut'=>$ut, 'cw'=>$cw, 'ttffile'=>$ttffile, 'fontkey'=>$fontkey, 'subsets'=>array(0=>range(0,127)), 'subsetfontids'=>array($i), 'used'=>false, 'indic'=>$indic, 'sip'=>$sip, 'sipext'=>$sipext, 'smp'=>$smp, 'TTCfontID' => $TTCfontID, 'unAGlyphs' => false);	// mPDF 5.4.05
	}
	else  {
		$ss = array();
		for ($s=32; $s<128; $s++) { $ss[$s] = $s; }
		$this->fonts[$fontkey] = array('i'=>$i, 'type'=>$type, 'name'=>$name, 'desc'=>$desc, 'panose'=>$panose, 'up'=>$up, 'ut'=>$ut, 'cw'=>$cw, 'ttffile'=>$ttffile, 'fontkey'=>$fontkey, 'subset'=>$ss, 'used'=>false, 'indic'=>$indic, 'sip'=>$sip, 'sipext'=>$sipext, 'smp'=>$smp, 'TTCfontID' => $TTCfontID, 'unAGlyphs' => $unAGlyphs);	// mPDF 5.4.05
	}
	if ($this->useKerning && $haskerninfo) { $this->fonts[$fontkey]['kerninfo'] = $kerninfo; }
	$this->FontFiles[$fontkey]=array('length1'=>$originalsize, 'type'=>"TTF", 'ttffile'=>$ttffile, 'sip'=>$sip, 'smp'=>$smp);
	unset($cw);
}



function SetFont($family,$style='',$size=0, $write=true, $forcewrite=false) {
	$family=strtolower($family);
	if (!$this->onlyCoreFonts) {
		if ($family == 'sans' || $family == 'sans-serif') { $family = $this->sans_fonts[0]; }
		if ($family == 'serif') { $family = $this->serif_fonts[0]; }
		if ($family == 'mono' || $family == 'monospace') { $family = $this->mono_fonts[0]; }
	}
	if (isset($this->fonttrans[$family]) && $this->fonttrans[$family]) { $family = $this->fonttrans[$family]; }
	if($family=='') {
		if ($this->FontFamily) { $family=$this->FontFamily; }
		else if ($this->default_font) { $family=$this->default_font; }
		else { $this->Error("No font or default font set!"); }
	}
	$this->ReqFontStyle = $style;	// required or requested style - used later for artificial bold/italic

	if (($family == 'csymbol') || ($family == 'czapfdingbats')  || ($family == 'ctimes')  || ($family == 'ccourier') || ($family == 'chelvetica')) {
		if ($this->PDFA || $this->PDFX) {
		   if ($family == 'csymbol' || $family == 'czapfdingbats') {
			$this->Error("Symbol and Zapfdingbats cannot be embedded in mPDF (required for PDFA1-b or PDFX/1-a).");
		   }
		   if ($family == 'ctimes'  || $family == 'ccourier' || $family == 'chelvetica') {
			if (($this->PDFA && !$this->PDFAauto) || ($this->PDFX && !$this->PDFXauto)) { $this->PDFAXwarnings[] = "Core Adobe font ".ucfirst($family)." cannot be embedded in mPDF, which is required for PDFA1-b or PDFX/1-a. (Embedded font will be substituted.)"; }
			if ($family == 'chelvetica') { $family = 'sans'; }
			if ($family == 'ctimes') { $family = 'serif'; }
			if ($family == 'ccourier') { $family = 'mono'; }
		   }
		   $this->usingCoreFont = false;
		}
		else { $this->usingCoreFont = true; }
		if($family=='csymbol' || $family=='czapfdingbats') { $style=''; }
	}
	else {  $this->usingCoreFont = false; }

	$this->U=false;
	$this->S=false;
	if ($style) {
		$style=strtoupper($style);
		if(strpos($style,'U')!==false) {
			$this->U=true;
			$style=str_replace('U','',$style);
		}
		if(strpos($style,'S')!==false) {
			$this->S=true;
			// Small Caps
			if (empty($this->upperCase)) { @include(_MPDF_PATH.'includes/upperCase.php'); }
			$style=str_replace('S','',$style);
		}
		if ($style=='IB') $style='BI';
	}
	if ($size==0) $size=$this->FontSizePt;

	$fontkey=$family.$style;

	$stylekey = $style;
	if (!$stylekey) { $stylekey = "R"; }

	if (!$this->onlyCoreFonts && !$this->usingCoreFont) {
		if(!isset($this->fonts[$fontkey]) || count($this->default_available_fonts) != count($this->available_unifonts) ) { // not already added
		  if (!in_array($fontkey,$this->available_unifonts)) {
			// If font[nostyle] exists - set it
			if (in_array($family,$this->available_unifonts)) {
				$style = '';
			}

			// Else if only one font available - set it (assumes if only one font available it will not have a style)
			else if (count($this->available_unifonts) == 1) {
				$family = $this->available_unifonts[0];
				$style = '';
			}

			else {
				$found = 0;
				// else substitute font of similar type
				if (in_array($family,$this->sans_fonts)) {
					$i = array_intersect($this->sans_fonts,$this->available_unifonts);
					if (count($i)) {
						$i = array_values($i);
						// with requested style if possible
						if (!in_array(($i[0].$style),$this->available_unifonts)) {
							$style = '';
						}
						$family = $i[0];
						$found = 1;
					}
				}
				else if (in_array($family,$this->serif_fonts)) {
					$i = array_intersect($this->serif_fonts,$this->available_unifonts);
					if (count($i)) {
						$i = array_values($i);
						// with requested style if possible
						if (!in_array(($i[0].$style),$this->available_unifonts)) {
							$style = '';
						}
						$family = $i[0];
						$found = 1;
					}
				}
				else if (in_array($family,$this->mono_fonts)) {
					$i = array_intersect($this->mono_fonts,$this->available_unifonts);
					if (count($i)) {
						$i = array_values($i);
						// with requested style if possible
						if (!in_array(($i[0].$style),$this->available_unifonts)) {
							$style = '';
						}
						$family = $i[0];
						$found = 1;
					}
				}

				if (!$found) {
					// set first available font
					$fs = $this->available_unifonts[0];
					preg_match('/^([a-z_0-9\-]+)([BI]{0,2})$/',$fs,$fas);	// Allow "-"
					// with requested style if possible
					$ws = $fas[1].$style;
					if (in_array($ws,$this->available_unifonts)) {
						$family = $fas[1]; // leave $style as is
					}
					else if (in_array($fas[1],$this->available_unifonts)) {
					// or without style
						$family = $fas[1];
						$style = '';
					}
					else {
					// or with the style specified
						$family = $fas[1];
						$style = $fas[2];
					}
				}
			}
			$fontkey = $family.$style;
		  }
		}
		// try to add font (if not already added)
		$this->AddFont($family, $style);

		//Test if font is already selected
		if($this->FontFamily == $family && $this->FontFamily == $this->currentfontfamily && $this->FontStyle == $style && $this->FontStyle == $this->currentfontstyle && $this->FontSizePt == $size && $this->FontSizePt == $this->currentfontsize && !$forcewrite) {
			return $family;
		}

		$fontkey = $family.$style;

		//Select it
		$this->FontFamily = $family;
		$this->FontStyle = $style;
		$this->FontSizePt = $size;
		$this->FontSize = $size / _MPDFK;
		$this->CurrentFont = &$this->fonts[$fontkey];
		if ($write) {
			$fontout = (sprintf('BT /F%d %.3F Tf ET', $this->CurrentFont['i'], $this->FontSizePt));
			if($this->page>0 && ((isset($this->pageoutput[$this->page]['Font']) && $this->pageoutput[$this->page]['Font'] != $fontout) || !isset($this->pageoutput[$this->page]['Font']) || $this->keep_block_together)) { $this->_out($fontout); }
			$this->pageoutput[$this->page]['Font'] = $fontout;
		}



		// Added - currentfont (lowercase) used in HTML2PDF
		$this->currentfontfamily=$family;
		$this->currentfontsize=$size;
		$this->currentfontstyle=$style.($this->U ? 'U' : '').($this->S ? 'S' : '');
		$this->setMBencoding('UTF-8');
	}

	else { 	// if using core fonts


		if ($this->PDFA || $this->PDFX) {
			$this->Error('Core Adobe fonts cannot be embedded in mPDF (required for PDFA1-b or PDFX/1-a) - cannot use option to use core fonts.');
		}
		$this->setMBencoding('windows-1252');

		//Test if font is already selected
		if(($this->FontFamily == $family) AND ($this->FontStyle == $style) AND ($this->FontSizePt == $size) && !$forcewrite) {
			return $family;
		}

		if (!isset($this->CoreFonts[$fontkey])) {
			if (in_array($family,$this->serif_fonts)) { $family = 'ctimes'; }
			else if (in_array($family,$this->mono_fonts)) { $family = 'ccourier'; }
			else { $family = 'chelvetica'; }
			$this->usingCoreFont = true;
			$fontkey = $family.$style;
		}

		if(!isset($this->fonts[$fontkey])) 	{
			// STANDARD CORE FONTS
			if (isset($this->CoreFonts[$fontkey])) {
				//Load metric file
				$file=$family;
				if($family=='ctimes' || $family=='chelvetica' || $family=='ccourier') { $file.=strtolower($style); }
				$file.='.php';
				include(_MPDF_PATH.'font/'.$file);
				if(!isset($cw)) { $this->Error('Could not include font metric file'); }
				$i=count($this->fonts)+$this->extraFontSubsets+1;
				$this->fonts[$fontkey]=array('i'=>$i,'type'=>'core','name'=>$this->CoreFonts[$fontkey],'desc'=>$desc,'up'=>$up,'ut'=>$ut,'cw'=>$cw);
				if ($this->useKerning) { $this->fonts[$fontkey]['kerninfo'] = $kerninfo; }
			}
			else {
				die('mPDF error - Font not defined');
			}
		}
		//Test if font is already selected
		if(($this->FontFamily == $family) AND ($this->FontStyle == $style) AND ($this->FontSizePt == $size) && !$forcewrite) {
			return $family;
		}
		//Select it
		$this->FontFamily=$family;
		$this->FontStyle=$style;
		$this->FontSizePt=$size;
		$this->FontSize=$size/_MPDFK;
		$this->CurrentFont=&$this->fonts[$fontkey];
		if ($write) {
			$fontout = (sprintf('BT /F%d %.3F Tf ET', $this->CurrentFont['i'], $this->FontSizePt));
			if($this->page>0 && ((isset($this->pageoutput[$this->page]['Font']) && $this->pageoutput[$this->page]['Font'] != $fontout) || !isset($this->pageoutput[$this->page]['Font']) || $this->keep_block_together)) { $this->_out($fontout); }
			$this->pageoutput[$this->page]['Font'] = $fontout;
		}
		// Added - currentfont (lowercase) used in HTML2PDF
		$this->currentfontfamily=$family;
		$this->currentfontsize=$size;
		$this->currentfontstyle=$style.($this->U ? 'U' : '').($this->S ? 'S' : '');

	}

	return $family;
}

function SetFontSize($size,$write=true) {
	//Set font size in points
	if($this->FontSizePt==$size) return;
	$this->FontSizePt=$size;
	$this->FontSize=$size/_MPDFK;
	$this->currentfontsize=$size;
		if ($write) {
			$fontout = (sprintf('BT /F%d %.3F Tf ET', $this->CurrentFont['i'], $this->FontSizePt));
			// Edited mPDF 3.0
			if($this->page>0 && ((isset($this->pageoutput[$this->page]['Font']) && $this->pageoutput[$this->page]['Font'] != $fontout) || !isset($this->pageoutput[$this->page]['Font']) || $this->keep_block_together)) { $this->_out($fontout); }
			$this->pageoutput[$this->page]['Font'] = $fontout;
		}
}

function AddLink() {
	//Create a new internal link
	$n=count($this->links)+1;
	$this->links[$n]=array(0,0);
	return $n;
}

function SetLink($link,$y=0,$page=-1) {
	//Set destination of internal link
	if($y==-1) $y=$this->y;
	if($page==-1)	$page=$this->page;
	$this->links[$link]=array($page,$y);
}

function Link($x,$y,$w,$h,$link) {
	$l = array($x*_MPDFK,$this->hPt-$y*_MPDFK,$w*_MPDFK,$h*_MPDFK,$link);
	if ($this->keep_block_together) {	// Save to array - don't write yet
		$this->ktLinks[$this->page][]= $l;
		return;
	}
	else if ($this->table_rotate) {	// *TABLES*
		$this->tbrot_Links[$this->page][]= $l;	// *TABLES*
		return;	// *TABLES*
	}	// *TABLES*
	else if ($this->kwt) {
		$this->kwt_Links[$this->page][]= $l;
		return;
	}

	if ($this->writingHTMLheader || $this->writingHTMLfooter) {
		$this->HTMLheaderPageLinks[]= $l;
		return;
	}
	//Put a link on the page
	$this->PageLinks[$this->page][]= $l;
	// Save cross-reference to Column buffer

}

function Text($x,$y,$txt) {
	// Output a string
	// Called (internally) by Watermark and _tableWrite [rotated cells]
	// Expects input to be mb_encoded if necessary and RTL reversed

	// ARTIFICIAL BOLD AND ITALIC
	$s = 'q ';
	if ($this->falseBoldWeight && strpos($this->ReqFontStyle,"B") !== false && strpos($this->FontStyle,"B") === false) {
		$s  .= '2 Tr 1 J 1 j ';
		$s .= sprintf('%.3F w ',($this->FontSize/130)*_MPDFK*$this->falseBoldWeight);
		$tc = strtoupper($this->TextColor); // change 0 0 0 rg to 0 0 0 RG
		if($this->FillColor!=$tc) { $s .= $tc.' '; }		// stroke (outline) = same colour as text(fill)
	}
	if (strpos($this->ReqFontStyle,"I") !== false && strpos($this->FontStyle,"I") === false) {
		$aix = '1 0 0.261799 1 %.3F %.3F Tm';
	}
	else { $aix = '%.3F %.3F Td'; }

	if($this->ColorFlag) $s.=$this->TextColor.' ';

	$this->CurrentFont['used']= true;
	if ($this->CurrentFont['type']=='TTF' && ($this->CurrentFont['sip'] || $this->CurrentFont['smp'])) {
	      $txt2 = str_replace(chr(194).chr(160),chr(32),$txt);
		$txt2 = $this->UTF8toSubset($txt2);
		$s.=sprintf('BT '.$aix.' %s Tj ET ',$x*_MPDFK,($this->h-$y)*_MPDFK,$txt2);
	}
	else if (!$this->usingCoreFont) {
	      $txt2 = str_replace(chr(194).chr(160),chr(32),$txt);
		$this->UTF8StringToArray($txt2);	// this is just to add chars to subset list
		if ($this->kerning && $this->useKerning) { $s .= $this->_kern($txt2, '', $aix, $x, $y); }
		else {
			//Convert string to UTF-16BE without BOM
			$txt2= $this->UTF8ToUTF16BE($txt2, false);
			$s.=sprintf('BT '.$aix.' (%s) Tj ET ',$x*_MPDFK,($this->h-$y)*_MPDFK,$this->_escape($txt2));
		}
	}
	else {
	      $txt2 = str_replace(chr(160),chr(32),$txt);
		if ($this->kerning && $this->useKerning) { $s .= $this->_kern($txt2, '', $aix, $x, $y); }
		else {
			$s.=sprintf('BT '.$aix.' (%s) Tj ET ',$x*_MPDFK,($this->h-$y)*_MPDFK,$this->_escape($txt2));
		}
	}
	if($this->U && $txt!='') {
		$c = strtoupper($this->TextColor); // change 0 0 0 rg to 0 0 0 RG
		if($this->FillColor!=$c) { $s.= ' '.$c.' '; }
		if (isset($this->CurrentFont['up'])) { $up=$this->CurrentFont['up']; }
		else { $up = -100; }
		$adjusty = (-$up/1000* $this->FontSize);
 		if (isset($this->CurrentFont['ut'])) { $ut=$this->CurrentFont['ut']/1000* $this->FontSize; }
		else { $ut = 60/1000* $this->FontSize; }
		$olw = $this->LineWidth;
		$s.=' '.(sprintf(' %.3F w',$ut*_MPDFK));
		$s.=' '.$this->_dounderline($x,$y + $adjusty,$txt);
		$s.=' '.(sprintf(' %.3F w',$olw*_MPDFK));
		if($this->FillColor!=$c) { $s.= ' '.$this->FillColor.' '; }
	}
   	// STRIKETHROUGH
	if($this->strike && $txt!='') {
		$c = strtoupper($this->TextColor); // change 0 0 0 rg to 0 0 0 RG
		if($this->FillColor!=$c) { $s.= ' '.$c.' '; }
    		//Superscript and Subscript Y coordinate adjustment (now for striked-through texts)
		if (isset($this->CurrentFont['desc']['CapHeight'])) { $ch=$this->CurrentFont['desc']['CapHeight']; }
		else { $ch = 700; }
		$adjusty = (-$ch/1000* $this->FontSize) * 0.35;
 		if (isset($this->CurrentFont['ut'])) { $ut=$this->CurrentFont['ut']/1000* $this->FontSize; }
		else { $ut = 60/1000* $this->FontSize; }
		$olw = $this->LineWidth;
		$s.=' '.(sprintf(' %.3F w',$ut*_MPDFK));
		$s.=' '.$this->_dounderline($x,$y+$adjusty,$txt);
		$s.=' '.(sprintf(' %.3F w',$olw*_MPDFK));
		if($this->FillColor!=$c) { $s.= ' '.$this->FillColor.' '; }
	}
	$s .= 'Q';
	$this->_out($s);
}



function ResetSpacing() {
	if ($this->ws != 0) { $this->_out('BT 0 Tw ET'); }
	$this->ws=0;
	if ($this->charspacing != 0) { $this->_out('BT 0 Tc ET'); }
	$this->charspacing=0;
}


function SetSpacing($cs,$ws) {
	if (intval($cs*1000)==0) { $cs = 0; }
	if ($cs) { $this->_out(sprintf('BT %.3F Tc ET',$cs)); }
	else if ($this->charspacing != 0) { $this->_out('BT 0 Tc ET'); }
	$this->charspacing=$cs;
	if (intval($ws*1000)==0) { $ws = 0; }
	if ($ws) { $this->_out(sprintf('BT %.3F Tw ET',$ws)); }
	else if ($this->ws != 0) { $this->_out('BT 0 Tw ET'); }
	$this->ws=$ws;
}

// WORD SPACING
function GetJspacing($nc,$ns,$w,$inclCursive) {
	$ws = 0;
	$charspacing = 0;
	$ww = $this->jSWord;
	$ncx = $nc-1;
	if ($nc == 0) { return array(0,0); }
	else if ($nc==1) { $charspacing = $w; }
	// Only word spacing allowed / possible
	else if ($this->fixedlSpacing !== false || $inclCursive) {
		if ($ns) { $ws = $w / $ns; }
	}
	else if (!$ns) {
		$charspacing = $w / ($ncx );
		if (($this->jSmaxChar > 0) && ($charspacing > $this->jSmaxChar)) {
			$charspacing = $this->jSmaxChar;
		}
	}
	else if ($ns == ($ncx )) {
		$charspacing = $w / $ns;
	}
	else {
		if ($this->usingCoreFont) {
			$cs = ($w * (1 - $this->jSWord)) / ($ncx );
			if (($this->jSmaxChar > 0) && ($cs > $this->jSmaxChar)) {
				$cs = $this->jSmaxChar;
				$ww = 1 - (($cs * ($ncx ))/$w);
			}
			$charspacing = $cs;
			$ws = ($w * ($ww) ) / $ns;
		}
		else {
			$cs = ($w * (1 - $this->jSWord)) / ($ncx -$ns);
			if (($this->jSmaxChar > 0) && ($cs > $this->jSmaxChar)) {
				$cs = $this->jSmaxChar;
				$ww = 1 - (($cs * ($ncx -$ns))/$w);
			}
			$charspacing = $cs;
			$ws = (($w * ($ww) ) / $ns) - $charspacing;
		}
	}
	return array($charspacing,$ws);
}

function Cell($w,$h=0,$txt='',$border=0,$ln=0,$align='',$fill=0,$link='', $currentx=0, $lcpaddingL=0, $lcpaddingR=0, $valign='M', $spanfill=0, $abovefont=0, $belowfont=0, $exactWidth=false) {
	//Output a cell
	// Expects input to be mb_encoded if necessary and RTL reversed
	// NON_BREAKING SPACE
	if ($this->usingCoreFont) {
	      $txt = str_replace(chr(160),chr(32),$txt);
	}
	else {
	      $txt = str_replace(chr(194).chr(160),chr(32),$txt);
	}

	$oldcolumn = $this->CurrCol;
	// Automatic page break
	// Allows PAGE-BREAK-AFTER = avoid to work

	if (!$this->tableLevel && (($this->y+$this->divheight>$this->PageBreakTrigger) || ($this->y+$h>$this->PageBreakTrigger) ||
		($this->y+($h*2)>$this->PageBreakTrigger && $this->blk[$this->blklvl]['page_break_after_avoid'])) and !$this->InFooter and $this->AcceptPageBreak()) {
		$x=$this->x;//Current X position


		// WORD SPACING
		$ws=$this->ws;//Word Spacing
		$charspacing=$this->charspacing;//Character Spacing
		$this->ResetSpacing();

		$this->AddPage($this->CurOrientation);
		// Added to correct for OddEven Margins
		$x += $this->MarginCorrection;
		if ($currentx) {
			$currentx += $this->MarginCorrection;
		}
		$this->x=$x;
		// WORD SPACING
		$this->SetSpacing($charspacing,$ws);
	}

	// Test: to put line through centre of cell: $this->Line($this->x,$this->y+($h/2),$this->x+50,$this->y+($h/2));


	// KEEP BLOCK TOGETHER Update/overwrite the lowest bottom of printing y value on first page
	if ($this->keep_block_together) {
		if ($h) { $this->ktBlock[$this->page]['bottom_margin'] = $this->y+$h; }
//		else { $this->ktBlock[$this->page]['bottom_margin'] = $this->y+$this->divheight; }
	}

	if($w==0) $w = $this->w-$this->rMargin-$this->x;
	$s='';
	if($fill==1 && $this->FillColor) {
		if((isset($this->pageoutput[$this->page]['FillColor']) && $this->pageoutput[$this->page]['FillColor'] != $this->FillColor) || !isset($this->pageoutput[$this->page]['FillColor']) || $this->keep_block_together) { $s .= $this->FillColor.' '; }
		$this->pageoutput[$this->page]['FillColor'] = $this->FillColor;
	}


	$boxtop = $this->y;
	$boxheight = $h;
	$boxbottom = $this->y+$h;

	if($txt!='') {
		// FONT SIZE - this determines the baseline caculation
		if ($this->linemaxfontsize && !$this->processingHeader) { $bfs = $this->linemaxfontsize; }
		else  { $bfs = $this->FontSize; }
    		//Calculate baseline Superscript and Subscript Y coordinate adjustment
		$bfx = $this->baselineC;
    		$baseline = $bfx*$bfs;
		if($this->SUP) { $baseline += ($bfx-1.05)*$this->FontSize; }
		else if($this->SUB) { $baseline += ($bfx + 0.04)*$this->FontSize; }
		else if($this->bullet) { $baseline += ($bfx-0.7)*$this->FontSize; }

		// Vertical align (for Images)
		if ($abovefont || $belowfont) {	// from flowing block - valign always M
			$va = $abovefont + (0.5*$bfs);
		}
		else if ($this->lineheight_correction) {
			if ($valign == 'T') { $va = (0.5 * $bfs * $this->lineheight_correction); }
			else if ($valign == 'B') { $va = $h-(0.5 * $bfs * $this->lineheight_correction); }
			else { $va = 0.5*$h; }	// Middle
		}
		else {
			if ($valign == 'T') { $va = (0.5 * $bfs * $this->default_lineheight_correction); }
			else if ($valign == 'B') { $va = $h-(0.5 * $bfs * $this->default_lineheight_correction); }
			else { $va = 0.5*$h; }	// Middle
		}

		// ONLY SET THESE IF WANT TO CONFINE BORDER +/- FILL TO FIT FONTSIZE - NOT FULL CELL AS IS ORIGINAL FUNCTION
		// spanfill or spanborder are set in FlowingBlock functions
		if ($spanfill || !empty($this->spanborddet) || $link!='') {
			$exth = 0.2;	// Add to fontsize to increase height of background / link / border
			$boxtop = $this->y+$baseline+$va-($this->FontSize*(1+$exth/2)*(0.5+$bfx));
			$boxheight = $this->FontSize * (1+$exth);
			$boxbottom = $boxtop + $boxheight;
		}
	}

	$bbw = $tbw = $lbw = $rbw = 0;	// Border widths
	if (!empty($this->spanborddet)) {
		if (!isset($this->spanborddet['B'])) { $this->spanborddet['B'] = array('s' => 0, 'style' => '', 'w' => 0); }
		if (!isset($this->spanborddet['T'])) { $this->spanborddet['T'] = array('s' => 0, 'style' => '', 'w' => 0); }
		if (!isset($this->spanborddet['L'])) { $this->spanborddet['L'] = array('s' => 0, 'style' => '', 'w' => 0); }
		if (!isset($this->spanborddet['R'])) { $this->spanborddet['R'] = array('s' => 0, 'style' => '', 'w' => 0); }
		$bbw = $this->spanborddet['B']['w'];
		$tbw = $this->spanborddet['T']['w'];
		$lbw = $this->spanborddet['L']['w'];
		$rbw = $this->spanborddet['R']['w'];
	}
	if($fill==1 || $border==1 || !empty($this->spanborddet)) {
		if (!empty($this->spanborddet)) {
			if ($fill==1) {
				$s.=sprintf('%.3F %.3F %.3F %.3F re f ',($this->x-$lbw)*_MPDFK,($this->h-$boxtop+$tbw)*_MPDFK,($w+$lbw+$rbw)*_MPDFK,(-$boxheight-$tbw-$bbw)*_MPDFK);
			}
			$s.= ' q ';
			$dashon = 3;
			$dashoff = 3.5;
			$dot = 2.5;
			if($tbw) {
				$short = 0;
				if ($this->spanborddet['T']['style'] == 'dashed') {
					$s.=sprintf(' 0 j 0 J [%.3F %.3F] 0 d ',$tbw*$dashon*_MPDFK,$tbw*$dashoff*_MPDFK);
				}
				else if ($this->spanborddet['T']['style'] == 'dotted') {
					$s.=sprintf(' 1 j 1 J [%.3F %.3F] %.3F d ',0.001,$tbw*$dot*_MPDFK,-$tbw/2*_MPDFK);
					$short = $tbw/2;
				}
				else {
					$s.=' 0 j 0 J [] 0 d ';
				}
				$c = $this->SetDColor($this->spanborddet['T']['c'],true);
				if ($this->spanborddet['T']['style'] == 'double') {
					$s.=sprintf(' %s %.3F w ',$c,$tbw/3*_MPDFK);
					$xadj = $xadj2 = 0;
					if ($this->spanborddet['L']['style'] == 'double') { $xadj = $this->spanborddet['L']['w']*2/3; }
					if ($this->spanborddet['R']['style'] == 'double') { $xadj2 = $this->spanborddet['R']['w']*2/3; }
					$s.=sprintf('%.3F %.3F m %.3F %.3F l S ',($this->x-$lbw)*_MPDFK,($this->h-$boxtop+$tbw*5/6)*_MPDFK,($this->x+$w+$rbw-$short)*_MPDFK,($this->h-$boxtop+$tbw*5/6)*_MPDFK);
					$s.=sprintf('%.3F %.3F m %.3F %.3F l S ',($this->x-$lbw+$xadj)*_MPDFK,($this->h-$boxtop+$tbw/6)*_MPDFK,($this->x+$w+$rbw-$short-$xadj2)*_MPDFK,($this->h-$boxtop+$tbw/6)*_MPDFK);
				}
				else {
					$s.=sprintf(' %s %.3F w ',$c,$tbw*_MPDFK);
					$s.=sprintf('%.3F %.3F m %.3F %.3F l S ',($this->x-$lbw)*_MPDFK,($this->h-$boxtop+$tbw/2)*_MPDFK,($this->x+$w+$rbw-$short)*_MPDFK,($this->h-$boxtop+$tbw/2)*_MPDFK);
				}
			}
			if($bbw) {
				$short = 0;
				if ($this->spanborddet['B']['style'] == 'dashed') {
					$s.=sprintf(' 0 j 0 J [%.3F %.3F] 0 d ',$bbw*$dashon*_MPDFK,$bbw*$dashoff*_MPDFK);
				}
				else if ($this->spanborddet['B']['style'] == 'dotted') {
					$s.=sprintf(' 1 j 1 J [%.3F %.3F] %.3F d ',0.001,$bbw*$dot*_MPDFK,-$bbw/2*_MPDFK);
					$short = $bbw/2;
				}
				else {
					$s.=' 0 j 0 J [] 0 d ';
				}
				$c = $this->SetDColor($this->spanborddet['B']['c'],true);
				if ($this->spanborddet['B']['style'] == 'double') {
					$s.=sprintf(' %s %.3F w ',$c,$bbw/3*_MPDFK);
					$xadj = $xadj2 = 0;
					if ($this->spanborddet['L']['style'] == 'double') { $xadj = $this->spanborddet['L']['w']*2/3; }
					if ($this->spanborddet['R']['style'] == 'double') { $xadj2 = $this->spanborddet['R']['w']*2/3; }
					$s.=sprintf('%.3F %.3F m %.3F %.3F l S ',($this->x-$lbw+$xadj)*_MPDFK,($this->h-$boxbottom-$bbw/6)*_MPDFK,($this->x+$w+$rbw-$short-$xadj2)*_MPDFK,($this->h-$boxbottom-$bbw/6)*_MPDFK);
					$s.=sprintf('%.3F %.3F m %.3F %.3F l S ',($this->x-$lbw)*_MPDFK,($this->h-$boxbottom-$bbw*5/6)*_MPDFK,($this->x+$w+$rbw-$short)*_MPDFK,($this->h-$boxbottom-$bbw*5/6)*_MPDFK);
				}
				else {
					$s.=sprintf(' %s %.3F w ',$c,$bbw*_MPDFK);
					$s.=sprintf('%.3F %.3F m %.3F %.3F l S ',($this->x-$lbw)*_MPDFK,($this->h-$boxbottom-$bbw/2)*_MPDFK,($this->x+$w+$rbw-$short)*_MPDFK,($this->h-$boxbottom-$bbw/2)*_MPDFK);
				}
			}
			if($lbw) {
				$short = 0;
				if ($this->spanborddet['L']['style'] == 'dashed') {
					$s.=sprintf(' 0 j 0 J [%.3F %.3F] 0 d ',$lbw*$dashon*_MPDFK,$lbw*$dashoff*_MPDFK);
				}
				else if ($this->spanborddet['L']['style'] == 'dotted') {
					$s.=sprintf(' 1 j 1 J [%.3F %.3F] %.3F d ',0.001,$lbw*$dot*_MPDFK,-$lbw/2*_MPDFK);
					$short = $lbw/2;
				}
				else {
					$s.=' 0 j 0 J [] 0 d ';
				}
				$c = $this->SetDColor($this->spanborddet['L']['c'],true);
				if ($this->spanborddet['L']['style'] == 'double') {
					$s.=sprintf(' %s %.3F w ',$c,$lbw/3*_MPDFK);
					$yadj = $yadj2 = 0;
					if ($this->spanborddet['T']['style'] == 'double') { $yadj = $this->spanborddet['T']['w']*2/3; }
					if ($this->spanborddet['B']['style'] == 'double') { $yadj2 = $this->spanborddet['B']['w']*2/3; }
					$s.=sprintf('%.3F %.3F m %.3F %.3F l S ',($this->x-$lbw/6)*_MPDFK,($this->h-$boxtop+$tbw-$yadj)*_MPDFK,($this->x-$lbw/6)*_MPDFK,($this->h-$boxbottom-$bbw+$short+$yadj2)*_MPDFK);
					$s.=sprintf('%.3F %.3F m %.3F %.3F l S ',($this->x-$lbw*5/6)*_MPDFK,($this->h-$boxtop+$tbw)*_MPDFK,($this->x-$lbw*5/6)*_MPDFK,($this->h-$boxbottom-$bbw+$short)*_MPDFK);
				}
				else {
					$s.=sprintf(' %s %.3F w ',$c,$lbw*_MPDFK);
					$s.=sprintf('%.3F %.3F m %.3F %.3F l S ',($this->x-$lbw/2)*_MPDFK,($this->h-$boxtop+$tbw)*_MPDFK,($this->x-$lbw/2)*_MPDFK,($this->h-$boxbottom-$bbw+$short)*_MPDFK);
				}
			}
			if($rbw) {
				$short = 0;
				if ($this->spanborddet['R']['style'] == 'dashed') {
					$s.=sprintf(' 0 j 0 J [%.3F %.3F] 0 d ',$rbw*$dashon*_MPDFK,$rbw*$dashoff*_MPDFK);
				}
				else if ($this->spanborddet['R']['style'] == 'dotted') {
					$s.=sprintf(' 1 j 1 J [%.3F %.3F] %.3F d ',0.001,$rbw*$dot*_MPDFK,-$rbw/2*_MPDFK);
					$short = $rbw/2;
				}
				else {
					$s.=' 0 j 0 J [] 0 d ';
				}
				$c = $this->SetDColor($this->spanborddet['R']['c'],true);
				if ($this->spanborddet['R']['style'] == 'double') {
					$s.=sprintf(' %s %.3F w ',$c,$rbw/3*_MPDFK);
					$yadj = $yadj2 = 0;
					if ($this->spanborddet['T']['style'] == 'double') { $yadj = $this->spanborddet['T']['w']*2/3; }
					if ($this->spanborddet['B']['style'] == 'double') { $yadj2 = $this->spanborddet['B']['w']*2/3; }
					$s.=sprintf('%.3F %.3F m %.3F %.3F l S ',($this->x+$w+$rbw/6)*_MPDFK,($this->h-$boxtop+$tbw-$yadj)*_MPDFK,($this->x+$w+$rbw/6)*_MPDFK,($this->h-$boxbottom-$bbw+$short+$yadj2)*_MPDFK);
					$s.=sprintf('%.3F %.3F m %.3F %.3F l S ',($this->x+$w+$rbw*5/6)*_MPDFK,($this->h-$boxtop+$tbw)*_MPDFK,($this->x+$w+$rbw*5/6)*_MPDFK,($this->h-$boxbottom-$bbw+$short)*_MPDFK);
				}
				else {
					$s.=sprintf(' %s %.3F w ',$c,$rbw*_MPDFK);
					$s.=sprintf('%.3F %.3F m %.3F %.3F l S ',($this->x+$w+$rbw/2)*_MPDFK,($this->h-$boxtop+$tbw)*_MPDFK,($this->x+$w+$rbw/2)*_MPDFK,($this->h-$boxbottom-$bbw+$short)*_MPDFK);
				}
			}
			$s.= ' Q ';
		}
		else {
			if ($fill==1) $op=($border==1) ? 'B' : 'f';
			else $op='S';
			$s.=sprintf('%.3F %.3F %.3F %.3F re %s ',$this->x*_MPDFK,($this->h-$boxtop)*_MPDFK,$w*_MPDFK,-$boxheight*_MPDFK,$op);
		}
	}

	if(is_string($border)) {
		$x=$this->x;
		$y=$this->y;
		if(is_int(strpos($border,'L')))
			$s.=sprintf('%.3F %.3F m %.3F %.3F l S ',$x*_MPDFK,($this->h-$boxtop)*_MPDFK,$x*_MPDFK,($this->h-($boxbottom))*_MPDFK);
		if(is_int(strpos($border,'T')))
			$s.=sprintf('%.3F %.3F m %.3F %.3F l S ',$x*_MPDFK,($this->h-$boxtop)*_MPDFK,($x+$w)*_MPDFK,($this->h-$boxtop)*_MPDFK);
		if(is_int(strpos($border,'R')))
			$s.=sprintf('%.3F %.3F m %.3F %.3F l S ',($x+$w)*_MPDFK,($this->h-$boxtop)*_MPDFK,($x+$w)*_MPDFK,($this->h-($boxbottom))*_MPDFK);
		if(is_int(strpos($border,'B')))
			$s.=sprintf('%.3F %.3F m %.3F %.3F l S ',$x*_MPDFK,($this->h-($boxbottom))*_MPDFK,($x+$w)*_MPDFK,($this->h-($boxbottom))*_MPDFK);
	}

	if($txt!='') {
		if ($exactWidth)
			$stringWidth = $w;
		else
			$stringWidth = $this->GetStringWidth($txt) + ( $this->charspacing * mb_strlen( $txt, $this->mb_enc ) / _MPDFK )
				 + ( $this->ws * mb_substr_count( $txt, ' ', $this->mb_enc ) / _MPDFK );

		// Set x OFFSET FOR PRINTING
		if($align=='R') {
			$dx=$w-$this->cMarginR - $stringWidth - $lcpaddingR;
		}
		elseif($align=='C') {
			$dx=(($w - $stringWidth )/2);
		}
		elseif($align=='L' or $align=='J') $dx=$this->cMarginL + $lcpaddingL;
    		else $dx = 0;

		if($this->ColorFlag) $s .='q '.$this->TextColor.' ';

		// OUTLINE
		if($this->outline_on && !$this->S) {
			$s .=' '.sprintf('%.3F w',$this->LineWidth*_MPDFK).' ';
			$s .=" $this->DrawColor ";
			$s .=" 2 Tr ";
    		}
		else if ($this->falseBoldWeight && strpos($this->ReqFontStyle,"B") !== false && strpos($this->FontStyle,"B") === false && !$this->S) {	// can't use together with OUTLINE or Small Caps
			$s .= ' 2 Tr 1 J 1 j ';
			$s .= ' '.sprintf('%.3F w',($this->FontSize/130)*_MPDFK*$this->falseBoldWeight).' ';
			$tc = strtoupper($this->TextColor); // change 0 0 0 rg to 0 0 0 RG
			if($this->FillColor!=$tc) { $s .= ' '.$tc.' '; }		// stroke (outline) = same colour as text(fill)
		}

		if (strpos($this->ReqFontStyle,"I") !== false && strpos($this->FontStyle,"I") === false) {	// Artificial italic
			$aix = '1 0 0.261799 1 %.3F %.3F Tm ';
		}
		else { $aix = '%.3F %.3F Td '; }

		// THE TEXT
		$sub = '';
		$this->CurrentFont['used']= true;

		// WORD SPACING
		// IF multibyte - Tw has no effect - need to use alternative method - do word spacing using an adjustment before each space
		if ($this->ws && !$this->usingCoreFont && !$this->CurrentFont['sip'] && !$this->CurrentFont['smp'] && !$this->S) {
		  $sub .= ' BT 0 Tw ET ';
		  if ($this->kerning && $this->useKerning) { $sub .= $this->_kern($txt, 'MBTw', $aix, ($this->x+$dx), ($this->y+$baseline+$va)); }
		  else {
			$space = " ";
			//Convert string to UTF-16BE without BOM
			$space= $this->UTF8ToUTF16BE($space , false);
			$space=$this->_escape($space );
			$sub .=sprintf('BT '.$aix,($this->x+$dx)*_MPDFK,($this->h-($this->y+$baseline+$va))*_MPDFK);
			$t = explode(' ',$txt);
			$sub .=sprintf(' %.3F Tc [',$this->charspacing);
			$numt = count($t);
			for($i=0;$i<$numt;$i++) {
				$tx = $t[$i];
				//Convert string to UTF-16BE without BOM
				$tx = $this->UTF8ToUTF16BE($tx , false);
				$tx = $this->_escape($tx);
				$sub .=sprintf('(%s) ',$tx);
				if (($i+1)<$numt) {
					$adj = -($this->ws)*1000/$this->FontSizePt;
					$sub .=sprintf('%d(%s) ',$adj,$space);
				}
			}
			$sub .='] TJ ';
			$sub .=' ET';
		  }
		}
		else {
		  $txt2= $txt;
		  if ($this->CurrentFont['type']=='TTF' && ($this->CurrentFont['sip'] || $this->CurrentFont['smp'])) {
			if ($this->S) { $sub .= $this->_smallCaps($txt2, 'SIPSMP', $aix, $dx, _MPDFK, $baseline, $va); }
			else {
				$txt2 = $this->UTF8toSubset($txt2);
				$sub .=sprintf('BT '.$aix.' %s Tj ET',($this->x+$dx)*_MPDFK,($this->h-($this->y+$baseline+$va))*_MPDFK,$txt2);
			}
		  }
		  else {
			if ($this->S) { $sub .= $this->_smallCaps($txt2, '', $aix, $dx, _MPDFK, $baseline, $va); }
			else if ($this->kerning && $this->useKerning) { $sub .= $this->_kern($txt2, '', $aix, ($this->x+$dx), ($this->y+$baseline+$va)); }
			else {
				if (!$this->usingCoreFont) {
					$txt2 = $this->UTF8ToUTF16BE($txt2, false);
				}
				$txt2=$this->_escape($txt2);
				$sub .=sprintf('BT '.$aix.' (%s) Tj ET',($this->x+$dx)*_MPDFK,($this->h-($this->y+$baseline+$va))*_MPDFK,$txt2);
			}
		  }
		}
		// UNDERLINE
		if($this->U) {
			$c = strtoupper($this->TextColor); // change 0 0 0 rg to 0 0 0 RG
			if($this->FillColor!=$c) { $sub .= ' '.$c.' '; }
			if (isset($this->CurrentFont['up'])) { $up=$this->CurrentFont['up']; }
			else { $up = -100; }
			$adjusty = (-$up/1000* $this->FontSize);
 			if (isset($this->CurrentFont['ut'])) { $ut=$this->CurrentFont['ut']/1000* $this->FontSize; }
			else { $ut = 60/1000* $this->FontSize; }
			$olw = $this->LineWidth;
			$sub .=' '.(sprintf(' %.3F w 0 j 0 J ',$ut*_MPDFK));
			$sub .=' '.$this->_dounderline($this->x+$dx,$this->y+$baseline+$va+$adjusty,$txt);
			$sub .=' '.(sprintf(' %.3F w 2 j 2 J ',$olw*_MPDFK));
			if($this->FillColor!=$c) { $sub .= ' '.$this->FillColor.' '; }
		}

   		// STRIKETHROUGH
		if($this->strike) {
			$c = strtoupper($this->TextColor); // change 0 0 0 rg to 0 0 0 RG
			if($this->FillColor!=$c) { $sub .= ' '.$c.' '; }
    			//Superscript and Subscript Y coordinate adjustment (now for striked-through texts)
			if (isset($this->CurrentFont['desc']['CapHeight'])) { $ch=$this->CurrentFont['desc']['CapHeight']; }
			else { $ch = 700; }
			$adjusty = (-$ch/1000* $this->FontSize) * 0.35;
 			if (isset($this->CurrentFont['ut'])) { $ut=$this->CurrentFont['ut']/1000* $this->FontSize; }
			else { $ut = 60/1000* $this->FontSize; }
			$olw = $this->LineWidth;
			$sub .=' '.(sprintf(' %.3F w 0 j 0 J ',$ut*_MPDFK));
			$sub .=' '.$this->_dounderline($this->x+$dx,$this->y+$baseline+$va+$adjusty,$txt);
			$sub .=' '.(sprintf(' %.3F w 2 j 2 J ',$olw*_MPDFK));
			if($this->FillColor!=$c) { $sub .= ' '.$this->FillColor.' '; }
		}

		// TEXT SHADOW
		if ($this->textshadow) {		// First to process is last in CSS comma separated shadows
			foreach($this->textshadow AS $ts) {
					$s .= ' q ';
					$s .= $this->SetTColor($ts['col'], true)."\n";
					if ($ts['col']{0}==5 && ord($ts['col']{4})<100) {	// RGBa
						$s .= $this->SetAlpha(ord($ts['col']{4})/100, 'Normal', true, 'F')."\n";
					}
					else if ($ts['col']{0}==6 && ord($ts['col']{5})<100) {	// CMYKa
						$s .= $this->SetAlpha(ord($ts['col']{5})/100, 'Normal', true, 'F')."\n";
					}
					else if ($ts['col']{0}==1 && $ts['col']{2}==1 && ord($ts['col']{3})<100) {	// Gray
						$s .= $this->SetAlpha(ord($ts['col']{3})/100, 'Normal', true, 'F')."\n";
					}
					$s .= sprintf(' 1 0 0 1 %.4F %.4F cm', $ts['x']*_MPDFK, -$ts['y']*_MPDFK)."\n";
					$s .= $sub;
					$s .= ' Q ';
			}
		}

		$s .= $sub;

		// COLOR
		if($this->ColorFlag) $s .=' Q';

		// LINK
		if($link!='') {
			$this->Link($this->x,$boxtop,$w,$boxheight,$link);
		}
	}
	if($s) $this->_out($s);

	// WORD SPACING
	if ($this->ws && !$this->usingCoreFont) {
		$this->_out(sprintf('BT %.3F Tc ET',$this->charspacing));
	}
	$this->lasth=$h;
	if( strpos($txt,"\n") !== false) $ln=1; // cell recognizes \n from <BR> tag
	if($ln>0)
	{
		//Go to next line
		$this->y += $h;
		if($ln==1) {
			//Move to next line
			if ($currentx != 0) { $this->x=$currentx; }
			else { $this->x=$this->lMargin; }
   		}
	}
	else $this->x+=$w;


}


function _kern($txt, $mode, $aix, $x, $y) {
   if ($mode == 'MBTw') {	// Multibyte requiring word spacing
		  $space = ' ';
		  //Convert string to UTF-16BE without BOM
		  $space= $this->UTF8ToUTF16BE($space , false);
		  $space=$this->_escape($space );
		  $s = sprintf(' BT '.$aix,$x*_MPDFK,($this->h-$y)*_MPDFK);
		  $t = explode(' ',$txt);
		  for($i=0;$i<count($t);$i++) {
			$tx = $t[$i];

			$tj = '(';
			$unicode = $this->UTF8StringToArray($tx);
			for($ti=0;$ti<count($unicode);$ti++) {
				if ($ti > 0 && isset($this->CurrentFont['kerninfo'][$unicode[($ti-1)]][$unicode[$ti]]))  {
							$kern = -$this->CurrentFont['kerninfo'][$unicode[($ti-1)]][$unicode[$ti]];
							$tj .= sprintf(')%d(',$kern);
				}
				$tc = code2utf($unicode[$ti]);
				$tc = $this->UTF8ToUTF16BE($tc, false);
				$tj .= $this->_escape($tc);
			}
			$tj .= ')';
			$s.=sprintf(' %.3F Tc [%s] TJ',$this->charspacing,$tj);


			if (($i+1)<count($t)) {
				$s.=sprintf(' %.3F Tc (%s) Tj',$this->ws+$this->charspacing,$space);
			}
		  }
		  $s.=' ET ';
   }
   else if (!$this->usingCoreFont) {
	$s = '';
	$tj = '(';
	$unicode = $this->UTF8StringToArray($txt);
	for($i=0;$i<count($unicode);$i++) {
		if ($i > 0 && isset($this->CurrentFont['kerninfo'][$unicode[($i-1)]][$unicode[$i]])) {
					$kern = -$this->CurrentFont['kerninfo'][$unicode[($i-1)]][$unicode[$i]];
					$tj .= sprintf(')%d(',$kern);
		}
		$tx = code2utf($unicode[$i]);
		$tx = $this->UTF8ToUTF16BE($tx, false);
		$tj .= $this->_escape($tx);
	}
	$tj .= ')';
	$s.=sprintf(' BT '.$aix.' [%s] TJ ET ',$x*_MPDFK,($this->h-$y)*_MPDFK,$tj);
   }
   else {	// CORE Font
	$s = '';
	$tj = '(';
	$l = strlen($txt);
	for($i=0;$i<$l;$i++) {
		if ($i > 0 && isset($this->CurrentFont['kerninfo'][$txt[($i-1)]][$txt[$i]])) {
			$kern = -$this->CurrentFont['kerninfo'][$txt[($i-1)]][$txt[$i]];
			$tj .= sprintf(')%d(',$kern);
		}
		$tj .= $this->_escape($txt[$i]);
	}
	$tj .= ')';
	$s.=sprintf(' BT '.$aix.' [%s] TJ ET ',$x*_MPDFK,($this->h-$y)*_MPDFK,$tj);
   }

   return $s;
}


function _smallCaps($txt, $mode, $aix, $dx, $k, $baseline, $va) {
	$upp = false;
	$str = array();
	$bits = array();
	if (!$this->usingCoreFont) {
	   $unicode = $this->UTF8StringToArray($txt);
	   foreach($unicode as $char) {
		if ($this->ws && $char == 32) { 	// space
			if (count($str)) { $bits[] = array($upp, $str, false); }
			$bits[] = array(false, array(32), true);
			$str = array();
			$upp = false;
		}
		else if (isset($this->upperCase[$char])) {
			if (!$upp) {
				if (count($str)) { $bits[] = array($upp, $str, false); }
				$str = array();
			}
			$str[] = $this->upperCase[$char];
			if ((!isset($this->CurrentFont['sip']) || !$this->CurrentFont['sip']) && (!isset($this->CurrentFont['smp']) || !$this->CurrentFont['smp'])) {
				$this->CurrentFont['subset'][$this->upperCase[$char]] = $this->upperCase[$char];
			}
			$upp = true;
		}
		else {
			if ($upp) {
				if (count($str)) { $bits[] = array($upp, $str, false); }
				$str = array();
			}
			$str[] = $char;
			$upp = false;
		}
	   }
	}
	else {
	   for($i=0;$i<strlen($txt);$i++) {
		if (isset($this->upperCase[ord($txt[$i])]) && $this->upperCase[ord($txt[$i])] < 256) {
			if (!$upp) {
				if (count($str)) { $bits[] = array($upp, $str, false); }
				$str = array();
			}
			$str[] = $this->upperCase[ord($txt[$i])];
			$upp = true;
		}
		else {
			if ($upp) {
				if (count($str)) { $bits[] = array($upp, $str, false); }
				$str = array();
			}
			$str[] = ord($txt[$i]);
			$upp = false;
		}
	   }
	}
	if (count($str)) { $bits[] = array($upp, $str, false); }

	$fid = $this->CurrentFont['i'];

	$s=sprintf(' BT '.$aix,($this->x+$dx)*$k,($this->h-($this->y+$baseline+$va))*$k);
	foreach($bits AS $b) {
		if ($b[0]) { $upp = true; }
		else { $upp = false; }

		$size = count ($b[1]);
		$txt = '';
		for ($i = 0; $i < $size; $i++) {
			$txt .= code2utf($b[1][$i]);
		}
		if ($this->usingCoreFont) {
			$txt = utf8_decode($txt);
		}
		if ($mode == 'SIPSMP') {
			$txt = $this->UTF8toSubset($txt);
		}
		else {
			if (!$this->usingCoreFont) {
				$txt = $this->UTF8ToUTF16BE($txt, false);
			}
			$txt=$this->_escape($txt);
			$txt = '('.$txt.')';
		}
		if ($b[2]) { // space
			$s.=sprintf(' /F%d %.3F Tf %d Tz', $fid, $this->FontSizePt, 100);
			$s.=sprintf(' %.3F Tc', ($this->charspacing+$this->ws));
			$s.=sprintf(' %s Tj', $txt);
		}
		else if ($upp) {
			$s.=sprintf(' /F%d %.3F Tf', $fid, $this->FontSizePt*$this->smCapsScale);
			$s.=sprintf(' %d Tz', $this->smCapsStretch);
			$s.=sprintf(' %.3F Tc', ($this->charspacing*100/$this->smCapsStretch));
			$s.=sprintf(' %s Tj', $txt);
		}
		else {
			$s.=sprintf(' /F%d %.3F Tf %d Tz', $fid, $this->FontSizePt, 100);
			$s.=sprintf(' %.3F Tc', ($this->charspacing));
			$s.=sprintf(' %s Tj', $txt);
		}
	}
	$s.=' ET ';
	return $s;
}


function MultiCell($w,$h,$txt,$border=0,$align='',$fill=0,$link='',$directionality='ltr',$encoded=false)
{
	// Parameter (pre-)encoded - When called internally from ToC or textarea: mb_encoding already done - but not reverse RTL/Indic
	if (!$encoded) {
		$txt = $this->purify_utf8_text($txt);
		if ($this->text_input_as_HTML) {
			$txt = $this->all_entities_to_utf8($txt);
		}
		if ($this->usingCoreFont) { $txt = mb_convert_encoding($txt,$this->mb_enc,'UTF-8'); }
		// Font-specific ligature substitution for Indic fonts
	}
	if (!$align) { $align = $this->defaultAlign; }

	//Output text with automatic or explicit line breaks
	$cw=&$this->CurrentFont['cw'];
	if($w==0)	$w=$this->w-$this->rMargin-$this->x;

	$wmax = ($w - ($this->cMarginL+$this->cMarginR));
	if ($this->usingCoreFont)  {
		$s=str_replace("\r",'',$txt);
		$nb=strlen($s);
		while($nb>0 and $s[$nb-1]=="\n")	$nb--;
	}
	else {
		$s=str_replace("\r",'',$txt);
		$nb=mb_strlen($s, $this->mb_enc );
		while($nb>0 and mb_substr($s,$nb-1,1,$this->mb_enc )=="\n")	$nb--;
	}
	$b=0;
	if($border) {
		if($border==1) {
			$border='LTRB';
			$b='LRT';
			$b2='LR';
		}
		else {
			$b2='';
			if(is_int(strpos($border,'L')))	$b2.='L';
			if(is_int(strpos($border,'R')))	$b2.='R';
			$b=is_int(strpos($border,'T')) ? $b2.'T' : $b2;
		}
	}
	$sep=-1;
	$i=0;
	$j=0;
	$l=0;
	$ns=0;
	$nl=1;



   if (!$this->usingCoreFont)  {
	$checkCursive=false;
	while($i<$nb) {
		//Get next character
		$c = mb_substr($s,$i,1,$this->mb_enc );
		if($c == "\n") {
			//Explicit line break
			// WORD SPACING
			$this->ResetSpacing();
			$tmp = rtrim(mb_substr($s,$j,$i-$j,$this->mb_enc));
			// DIRECTIONALITY

			$this->Cell($w,$h,$tmp,$b,2,$align,$fill,$link);
			$i++;
			$sep=-1;
			$j=$i;
			$l=0;
			$ns=0;
			$nl++;
			if($border and $nl==2) $b=$b2;
			continue;
		}
		if($c == " ") {
			$sep=$i;
			$ls=$l;
			$ns++;
		}

		$l += $this->GetCharWidthNonCore($c);

		if($l>$wmax) {
			//Automatic line break
			if($sep==-1) {	// Only one word
				if($i==$j) $i++;
				// WORD SPACING
				$this->ResetSpacing();
				$tmp = rtrim(mb_substr($s,$j,$i-$j,$this->mb_enc));
				// DIRECTIONALITY

				$this->Cell($w,$h,$tmp,$b,2,$align,$fill,$link);
			}
			else {
				$tmp = rtrim(mb_substr($s,$j,$sep-$j,$this->mb_enc));
				if($align=='J') {
					//////////////////////////////////////////
					// JUSTIFY J using Unicode fonts (Word spacing doesn't work)
					// WORD SPACING UNICODE
					// Change NON_BREAKING SPACE to spaces so they are 'spaced' properly
					$tmp = str_replace(chr(194).chr(160),chr(32),$tmp );
					$len_ligne = $this->GetStringWidth($tmp );
					$nb_carac = mb_strlen( $tmp , $this->mb_enc ) ;
					$nb_spaces = mb_substr_count( $tmp ,' ', $this->mb_enc ) ;

					$inclCursive=false;
					if ($checkCursive) {
					}
					list($charspacing,$ws) = $this->GetJspacing($nb_carac,$nb_spaces,((($wmax) - $len_ligne) * _MPDFK),$inclCursive);
					$this->SetSpacing($charspacing,$ws);
					//////////////////////////////////////////
				}

				// DIRECTIONALITY

				$this->Cell($w,$h,$tmp,$b,2,$align,$fill,$link);
				$i=$sep+1;
			}
			$sep=-1;
			$j=$i;
			$l=0;
			$ns=0;
			$nl++;
			if($border and $nl==2) $b=$b2;
		}
		else $i++;
	}
	//Last chunk
	// WORD SPACING

	$this->ResetSpacing();

   }


   else {

	while($i<$nb) {
		//Get next character
		$c=$s[$i];
		if($c == "\n") {
			//Explicit line break
			// WORD SPACING
			$this->ResetSpacing();
			$this->Cell($w,$h,substr($s,$j,$i-$j),$b,2,$align,$fill,$link);
			$i++;
			$sep=-1;
			$j=$i;
			$l=0;
			$ns=0;
			$nl++;
			if($border and $nl==2) $b=$b2;
			continue;
		}
		if($c == " ") {
			$sep=$i;
			$ls=$l;
			$ns++;
		}

		$l += $this->GetCharWidthCore($c);
		if($l>$wmax) {
			//Automatic line break
			if($sep==-1) {
				if($i==$j) $i++;
				// WORD SPACING
				$this->ResetSpacing();
				$this->Cell($w,$h,substr($s,$j,$i-$j),$b,2,$align,$fill,$link);
			}
			else {
				if($align=='J') {
					$tmp = rtrim(substr($s,$j,$sep-$j));
					//////////////////////////////////////////
					// JUSTIFY J using Unicode fonts (Word spacing doesn't work)
					// WORD SPACING NON_UNICDOE/CJK
					// Change NON_BREAKING SPACE to spaces so they are 'spaced' properly
					$tmp = str_replace(chr(160),chr(32),$tmp);
					$len_ligne = $this->GetStringWidth($tmp );
					$nb_carac = strlen( $tmp ) ;
					$nb_spaces = substr_count( $tmp ,' ' ) ;
					list($charspacing,$ws) = $this->GetJspacing($nb_carac,$nb_spaces,((($wmax) - $len_ligne) * _MPDFK),false);
					$this->SetSpacing($charspacing,$ws);
					//////////////////////////////////////////
				}
				$this->Cell($w,$h,substr($s,$j,$sep-$j),$b,2,$align,$fill,$link);
				$i=$sep+1;
			}
			$sep=-1;
			$j=$i;
			$l=0;
			$ns=0;
			$nl++;
			if($border and $nl==2) $b=$b2;
		}
		else $i++;
	}
	//Last chunk
	// WORD SPACING

	$this->ResetSpacing();

   }
	//Last chunk
   if($border and is_int(strpos($border,'B')))	$b.='B';
   if (!$this->usingCoreFont)  {
		$tmp = rtrim(mb_substr($s,$j,$i-$j,$this->mb_enc));
		// DIRECTIONALITY
   		$this->Cell($w,$h,$tmp,$b,2,$align,$fill,$link);
   }
   else { $this->Cell($w,$h,substr($s,$j,$i-$j),$b,2,$align,$fill,$link); }
   $this->x=$this->lMargin;
}




function saveInlineProperties() {
	$saved = array();
	$saved[ 'family' ] = $this->FontFamily;
	$saved[ 'style' ] = $this->FontStyle;
	$saved[ 'sizePt' ] = $this->FontSizePt;
	$saved[ 'size' ] = $this->FontSize;
	$saved[ 'HREF' ] = $this->HREF;
	$saved[ 'underline' ] = $this->U;
	$saved[ 'smCaps' ] = $this->S;
	$saved[ 'strike' ] = $this->strike;
	$saved[ 'textshadow' ] = $this->textshadow;
	$saved[ 'SUP' ] = $this->SUP;
	$saved[ 'SUB' ] = $this->SUB;
	$saved[ 'linewidth' ] = $this->LineWidth;
	$saved[ 'drawcolor' ] = $this->DrawColor;
	$saved[ 'is_outline' ] = $this->outline_on;
	$saved[ 'outlineparam' ] = $this->outlineparam;
	$saved[ 'toupper' ] = $this->toupper;
	$saved[ 'tolower' ] = $this->tolower;
	$saved[ 'capitalize' ] = $this->capitalize;
	$saved[ 'fontkerning' ] = $this->kerning;
	$saved[ 'lSpacingCSS' ] = $this->lSpacingCSS;
	$saved[ 'wSpacingCSS' ] = $this->wSpacingCSS;
	$saved[ 'I' ] = $this->I;
	$saved[ 'B' ] = $this->B;
	$saved[ 'colorarray' ] = $this->colorarray;
	$saved[ 'bgcolorarray' ] = $this->spanbgcolorarray;
	$saved[ 'border' ] = $this->spanborddet;
	$saved[ 'color' ] = $this->TextColor;
	$saved[ 'bgcolor' ] = $this->FillColor;
	$saved['lang'] = $this->currentLang;
	$saved['display_off'] = $this->inlineDisplayOff;

	return $saved;
}

function restoreInlineProperties( &$saved) {
	$FontFamily = $saved[ 'family' ];
	$this->FontStyle = $saved[ 'style' ];
	$this->FontSizePt = $saved[ 'sizePt' ];
	$this->FontSize = $saved[ 'size' ];

	$this->currentLang =  $saved['lang'];
	if ($this->useLang && !$this->usingCoreFont) {
	  if ($this->currentLang != $this->default_lang && ((strlen($this->currentLang) == 5 && $this->currentLang != 'UTF-8') || strlen($this->currentLang ) == 2)) {
		list ($coreSuitable,$mpdf_pdf_unifonts) = GetLangOpts($this->currentLang, $this->useAdobeCJK);
		if ($mpdf_pdf_unifonts) { $this->RestrictUnicodeFonts($mpdf_pdf_unifonts); }
		else { $this->RestrictUnicodeFonts($this->default_available_fonts ); }
	  }
	  else {
		$this->RestrictUnicodeFonts($this->default_available_fonts );
	  }
	}

	$this->ColorFlag = ($this->FillColor != $this->TextColor); //Restore ColorFlag as well

	$this->HREF = $saved[ 'HREF' ];
	$this->U = $saved[ 'underline' ];
	$this->S = $saved[ 'smCaps' ];
	$this->strike = $saved[ 'strike' ];
	$this->textshadow = $saved[ 'textshadow' ];
	$this->SUP = $saved[ 'SUP' ];
	$this->SUB = $saved[ 'SUB' ];
	$this->LineWidth = $saved[ 'linewidth' ];
	$this->DrawColor = $saved[ 'drawcolor' ];
	$this->outline_on = $saved[ 'is_outline' ];
	$this->outlineparam = $saved[ 'outlineparam' ];
	$this->inlineDisplayOff = $saved['display_off'];

	$this->toupper = $saved[ 'toupper' ];
	$this->tolower = $saved[ 'tolower' ];
	$this->capitalize = $saved[ 'capitalize' ];
	$this->kerning = $saved[ 'fontkerning' ];
	$this->lSpacingCSS = $saved[ 'lSpacingCSS' ];
	if (($this->lSpacingCSS || $this->lSpacingCSS==='0') && strtoupper($this->lSpacingCSS) != 'NORMAL') {
		$this->fixedlSpacing = $this->ConvertSize($this->lSpacingCSS,$this->FontSize);
	}
	else { $this->fixedlSpacing = false; }
	$this->wSpacingCSS = $saved[ 'wSpacingCSS' ];
	if ($this->wSpacingCSS && strtoupper($this->wSpacingCSS) != 'NORMAL') {
		$this->minwSpacing = $this->ConvertSize($this->wSpacingCSS,$this->FontSize);
	}
	else { $this->minwSpacing = 0; }
  
	$this->SetFont($FontFamily, $saved[ 'style' ].($this->U ? 'U' : '').($this->S ? 'S' : ''),$saved[ 'sizePt' ],false);

	$this->currentfontstyle = $saved[ 'style' ].($this->U ? 'U' : '').($this->S ? 'S' : '');
	$this->currentfontsize = $saved[ 'sizePt' ];
	$this->SetStylesArray(array('S'=>$this->S, 'U'=>$this->U, 'B'=>$saved[ 'B' ], 'I'=>$saved[ 'I' ]));

	$this->TextColor = $saved[ 'color' ];
	$this->FillColor = $saved[ 'bgcolor' ];
	$this->colorarray = $saved[ 'colorarray' ];
	$cor = $saved[ 'colorarray' ];
	if ($cor) $this->SetTColor($cor);
	$this->spanbgcolorarray = $saved[ 'bgcolorarray' ];
	$cor = $saved[ 'bgcolorarray' ];
	if ($cor) $this->SetFColor($cor);
	$this->spanborddet = $saved[ 'border' ];
}



// Used when ColActive for tables - updated to return first block with background fill OR borders
function GetFirstBlockFill() {
	// Returns the first blocklevel that uses a bgcolor fill
	$startfill = 0;
	for ($i=1;$i<=$this->blklvl;$i++) {
		if ($this->blk[$i]['bgcolor'] || $this->blk[$i]['border_left']['w'] || $this->blk[$i]['border_right']['w']  || $this->blk[$i]['border_top']['w']  || $this->blk[$i]['border_bottom']['w']  ) {
			$startfill = $i;
			break;
		}
	}
	return $startfill;
}

function SetBlockFill($blvl) {
	if ($this->blk[$blvl]['bgcolor']) {
		$this->SetFColor($this->blk[$blvl]['bgcolorarray']);
		return 1;
	}
	else {
		$this->SetFColor($this->ConvertColor(255));
		return 0;
	}
}


//-------------------------FLOWING BLOCK------------------------------------//
//The following functions were originally written by Damon Kohler           //
//--------------------------------------------------------------------------//

function saveFont() {
	$saved = array();
	$saved[ 'family' ] = $this->FontFamily;
	$saved[ 'style' ] = $this->FontStyle;
	$saved[ 'sizePt' ] = $this->FontSizePt;
	$saved[ 'size' ] = $this->FontSize;
	$saved[ 'curr' ] = &$this->CurrentFont;
	$saved[ 'color' ] = $this->TextColor;
	$saved[ 'spanbgcolor' ] = $this->spanbgcolor;
	$saved[ 'spanbgcolorarray' ] = $this->spanbgcolorarray;
	$saved[ 'bord' ] = $this->spanborder;
	$saved[ 'border' ] = $this->spanborddet;
	$saved[ 'HREF' ] = $this->HREF;
	$saved[ 'underline' ] = $this->U;
	$saved[ 'smCaps' ] = $this->S;
	$saved[ 'strike' ] = $this->strike;
	$saved[ 'textshadow' ] = $this->textshadow;
	$saved[ 'SUP' ] = $this->SUP;
	$saved[ 'SUB' ] = $this->SUB;
	$saved[ 'linewidth' ] = $this->LineWidth;
	$saved[ 'drawcolor' ] = $this->DrawColor;
	$saved[ 'is_outline' ] = $this->outline_on;
	$saved[ 'outlineparam' ] = $this->outlineparam;
	$saved[ 'ReqFontStyle' ] = $this->ReqFontStyle;
	$saved[ 'fontkerning' ] = $this->kerning;
	$saved[ 'fixedlSpacing' ] = $this->fixedlSpacing;
	$saved[ 'minwSpacing' ] = $this->minwSpacing;
	return $saved;
}

function restoreFont( &$saved, $write=true) {
	if (!isset($saved) || empty($saved)) return;

	$this->FontFamily = $saved[ 'family' ];
	$this->FontStyle = $saved[ 'style' ];
	$this->FontSizePt = $saved[ 'sizePt' ];
	$this->FontSize = $saved[ 'size' ];
	$this->CurrentFont = &$saved[ 'curr' ];
	$this->TextColor = $saved[ 'color' ];
	$this->spanbgcolor = $saved[ 'spanbgcolor' ];
	$this->spanbgcolorarray = $saved[ 'spanbgcolorarray' ];
	$this->spanborder = $saved[ 'bord' ];
	$this->spanborddet = $saved[ 'border' ];
	$this->ColorFlag = ($this->FillColor != $this->TextColor); //Restore ColorFlag as well
	$this->HREF = $saved[ 'HREF' ];
	$this->U = $saved[ 'underline' ];
	$this->S = $saved[ 'smCaps' ];
	$this->kerning = $saved[ 'fontkerning' ];
	$this->fixedlSpacing = $saved[ 'fixedlSpacing' ];
	$this->minwSpacing = $saved[ 'minwSpacing' ];
	$this->strike = $saved[ 'strike' ];
	$this->textshadow = $saved[ 'textshadow' ];
	$this->SUP = $saved[ 'SUP' ];
	$this->SUB = $saved[ 'SUB' ];
	$this->LineWidth = $saved[ 'linewidth' ];
	$this->DrawColor = $saved[ 'drawcolor' ];
	$this->outline_on = $saved[ 'is_outline' ];
	$this->outlineparam = $saved[ 'outlineparam' ];
	if ($write) {
		$this->SetFont($saved[ 'family' ],$saved[ 'style' ].($this->U ? 'U' : '').($this->S ? 'S' : ''),$saved[ 'sizePt' ],true,true);	// force output
		$fontout = (sprintf('BT /F%d %.3F Tf ET', $this->CurrentFont['i'], $this->FontSizePt));
		if($this->page>0 && ((isset($this->pageoutput[$this->page]['Font']) && $this->pageoutput[$this->page]['Font'] != $fontout) || !isset($this->pageoutput[$this->page]['Font']) || $this->keep_block_together)) { $this->_out($fontout); }
		$this->pageoutput[$this->page]['Font'] = $fontout;
	}
	else
		$this->SetFont($saved[ 'family' ],$saved[ 'style' ].($this->U ? 'U' : '').($this->S ? 'S' : ''),$saved[ 'sizePt' ]);
	$this->ReqFontStyle = $saved[ 'ReqFontStyle' ];
}

function newFlowingBlock( $w, $h, $a = '', $is_table = false, $is_list = false, $blockstate = 0, $newblock=true, $blockdir='ltr')
{
	if (!$a) {
		if ($blockdir=='rtl') { $a = 'R'; }
		else { $a = 'L'; }
	}
	$this->flowingBlockAttr[ 'width' ] = ($w * _MPDFK);
	// line height in user units
	$this->flowingBlockAttr[ 'is_table' ] = $is_table;
	$this->flowingBlockAttr[ 'is_list' ] = $is_list;
	$this->flowingBlockAttr[ 'height' ] = $h;
	$this->flowingBlockAttr[ 'lineCount' ] = 0;
	$this->flowingBlockAttr[ 'align' ] = $a;
	$this->flowingBlockAttr[ 'font' ] = array();
	$this->flowingBlockAttr[ 'content' ] = array();
	$this->flowingBlockAttr[ 'contentB' ] = array();
	$this->flowingBlockAttr[ 'contentWidth' ] = 0;
	$this->flowingBlockAttr[ 'blockstate' ] = $blockstate;

	$this->flowingBlockAttr[ 'newblock' ] = $newblock;
	$this->flowingBlockAttr[ 'valign' ] = 'M';
	$this->flowingBlockAttr['blockdir'] = $blockdir;

}

function finishFlowingBlock($endofblock=false, $next='') {
	$currentx = $this->x;
	//prints out the last chunk
	$is_table = $this->flowingBlockAttr[ 'is_table' ];
	$is_list = $this->flowingBlockAttr[ 'is_list' ];
	$maxWidth =& $this->flowingBlockAttr[ 'width' ];
	$lineHeight =& $this->flowingBlockAttr[ 'height' ];
	$align =& $this->flowingBlockAttr[ 'align' ];
	$content =& $this->flowingBlockAttr[ 'content' ];
	$contentB =& $this->flowingBlockAttr[ 'contentB' ];
	$font =& $this->flowingBlockAttr[ 'font' ];
	$contentWidth =& $this->flowingBlockAttr[ 'contentWidth' ];
	$lineCount =& $this->flowingBlockAttr[ 'lineCount' ];
	$valign =& $this->flowingBlockAttr[ 'valign' ];
	$blockstate = $this->flowingBlockAttr[ 'blockstate' ];

	$newblock = $this->flowingBlockAttr[ 'newblock' ];
	$blockdir = $this->flowingBlockAttr['blockdir'];


	// *********** BLOCK BACKGROUND COLOR *****************//
	if ($this->blk[$this->blklvl]['bgcolor'] && !$is_table) {
		$fill = 0;
	}
	else {
		$this->SetFColor($this->ConvertColor(255));
		$fill = 0;
	}

	// Always right trim!
	// Right trim content and adjust width if need to justify (later)
		if (isset($content[count($content)-1]) && preg_match('/[ ]+$/',$content[count($content)-1], $m)) {
			$strip = strlen($m[0]);
			$content[count($content)-1] = substr($content[count($content)-1],0,(strlen($content[count($content)-1])-$strip));
			$this->restoreFont( $font[ count($content)-1 ],false );
			$contentWidth -= $this->GetStringWidth($m[0]) * _MPDFK;
		}

	// the amount of space taken up so far in user units
	$usedWidth = 0;

	// COLS
	$oldcolumn = $this->CurrCol;


	// Print out each chunk

	if ($is_table) {
		$ipaddingL = 0;
		$ipaddingR = 0;
		$paddingL = 0;
		$paddingR = 0;
	}
	else {
		$ipaddingL = $this->blk[$this->blklvl]['padding_left'];
		$ipaddingR = $this->blk[$this->blklvl]['padding_right'];
		$paddingL = ($ipaddingL * _MPDFK);
		$paddingR = ($ipaddingR * _MPDFK);
		$this->cMarginL =  $this->blk[$this->blklvl]['border_left']['w'];
		$this->cMarginR =  $this->blk[$this->blklvl]['border_right']['w'];

		// Added mPDF 3.0 Float DIV
		$fpaddingR = 0;
		$fpaddingL = 0;

		$usey = $this->y + 0.002;
		if (($newblock) && ($blockstate==1 || $blockstate==3) && ($lineCount == 0) ) {
			$usey += $this->blk[$this->blklvl]['margin_top'] + $this->blk[$this->blklvl]['padding_top'] + $this->blk[$this->blklvl]['border_top']['w'];
		}
	}	// *TABLES*

		// Set Current lineheight (correction factor)
		$lhfixed = false;
		if ($is_list) {
			if (preg_match('/([0-9.,]+)mm/',$this->list_lineheight[$this->listlvl][$this->listOcc],$am)) {
				$lhfixed = true;
				$def_fontsize = $this->InlineProperties['LISTITEM'][$this->listlvl][$this->listOcc][$this->listnum]['size'];
				$this->lineheight_correction = $am[1] / $def_fontsize ;
			}
			else {
				$this->lineheight_correction = $this->list_lineheight[$this->listlvl][$this->listOcc];
			}
		}
		else
		if ($is_table) {
			if (preg_match('/([0-9.,]+)mm/',$this->table_lineheight,$am)) {
				$lhfixed = true;
				$def_fontsize = $this->FontSize; 				// needs to be default font-size for block ****
				$this->lineheight_correction = $lineHeight / $def_fontsize ;
			}
			else {
				$this->lineheight_correction = $this->table_lineheight;
			}
		}
		else
		if (isset($this->blk[$this->blklvl]['line_height']) && $this->blk[$this->blklvl]['line_height']) {
			if (preg_match('/([0-9.,]+)mm/',$this->blk[$this->blklvl]['line_height'],$am)) {
				$lhfixed = true;
				$def_fontsize = $this->blk[$this->blklvl]['InlineProperties']['size']; 	// needs to be default font-size for block ****
				$this->lineheight_correction = $am[1] / $def_fontsize ;
			}
			else {
				$this->lineheight_correction = $this->blk[$this->blklvl]['line_height'];
			}
		}
		else {
			$this->lineheight_correction = $this->normalLineheight;
		}

		//  correct lineheight to maximum fontsize
		if ($lhfixed) { $maxlineHeight = $this->lineheight; }
		else { $maxlineHeight = 0; }
		$this->forceExactLineheight = true;
		$maxfontsize = 0;
		// While we're at it, check if contains cursive text
		$checkCursive=false;
		foreach ( $content as $k => $chunk )
		{
		  $this->restoreFont( $font[ $k ],false );
		  if (!isset($this->objectbuffer[$k])) {
			// Soft Hyphens chr(173)
			if (!$this->usingCoreFont) {
			      $content[$k] = $chunk = str_replace("\xc2\xad",'',$chunk );
			}
			else if ($this->FontFamily!='csymbol' && $this->FontFamily!='czapfdingbats') {
			      $content[$k] = $chunk = str_replace(chr(173),'',$chunk );
			}
			// Special case of sub/sup carried over on its own to last line
			if (($this->SUB || $this->SUP) && count($content)==1) { $actfs = $this->FontSize*100/55; } // 55% is font change for sub/sup
			else { $actfs = $this->FontSize; }
			if (!$lhfixed) { $maxlineHeight = max($maxlineHeight,$actfs * $this->lineheight_correction ); }
			if ($lhfixed && ($actfs > $def_fontsize || ($actfs > ($lineHeight * $this->lineheight_correction) && $is_list))) {
				$this->forceExactLineheight = false;
			}
			$maxfontsize = max($maxfontsize,$actfs);
		  }
		}

		if(isset($font[count($font)-1])) {
			$lastfontreqstyle = $font[count($font)-1]['ReqFontStyle'];
			$lastfontstyle = $font[count($font)-1]['style'];
		}
		else {
			$lastfontreqstyle=null;
			$lastfontstyle=null;
		}
		if ($blockdir == 'ltr' && strpos($lastfontreqstyle,"I") !== false && strpos($lastfontstyle,"I") === false) {	// Artificial italic
			$lastitalic = $this->FontSize*0.15*_MPDFK;
		}
		else { $lastitalic = 0; }


		if ($is_list && is_array($this->bulletarray) && count($this->bulletarray)) {
	  		$actfs = $this->bulletarray['fontsize'];
			if (!$lhfixed) { $maxlineHeight = max($maxlineHeight,$actfs * $this->lineheight_correction );  }
			if ($lhfixed && $actfs > $def_fontsize) { $this->forceExactLineheight = false; }
			$maxfontsize = max($maxfontsize,$actfs);
		}

		// when every text item checked i.e. $maxfontsize is set properly

		$af = 0; 	// Above font
		$bf = 0; 	// Below font
		$mta = 0;	// Maximum top-aligned
		$mba = 0;	// Maximum bottom-aligned

		foreach ( $content as $k => $chunk )
		{
		  if (isset($this->objectbuffer[$k])) {
			$oh = $this->objectbuffer[$k]['OUTER-HEIGHT'];
			$va = $this->objectbuffer[$k]['vertical-align']; // = $objattr['vertical-align'] = set as M,T,B,S
			if ($lhfixed && $oh > $def_fontsize) { $this->forceExactLineheight = false; }

			if ($va == 'BS') {	//  (BASELINE default)
				$af = max($af, ($oh - ($maxfontsize * (0.5 + $this->baselineC))));
			}
			else if ($va == 'M') {
				$af = max($af, ($oh - $maxfontsize)/2);
				$bf = max($bf, ($oh - $maxfontsize)/2);
			}
			else if ($va == 'TT') {
				$bf = max($bf, ($oh - $maxfontsize));
			}
			else if ($va == 'TB') {
				$af = max($af, ($oh - $maxfontsize));
			}
			else if ($va == 'T') {
				$mta = max($mta, $oh);
			}
			else if ($va == 'B') {
				$mba = max($mba, $oh);
			}
		  }
		}
		if ((!$lhfixed || !$this->forceExactLineheight) && ($af > (($maxlineHeight - $maxfontsize)/2) || $bf > (($maxlineHeight - $maxfontsize)/2))) {
			$maxlineHeight = $maxfontsize + $af + $bf;
		}
		else if (!$lhfixed) { $af = $bf = ($maxlineHeight - $maxfontsize)/2; }
		if ($mta > $maxlineHeight) {
			$bf += ($mta - $maxlineHeight);
			$maxlineHeight = $mta;
		}
		if ($mba > $maxlineHeight) {
			$af += ($mba - $maxlineHeight);
			$maxlineHeight = $mba;
		}

		$lineHeight = $maxlineHeight;
		// If NOT images, and maxfontsize NOT > lineHeight - this value determines text baseline positioning
		if ($lhfixed && $af==0 && $bf==0 && $maxfontsize<=($def_fontsize * $this->lineheight_correction * 0.8 )) {
			$this->linemaxfontsize = $def_fontsize;
		}
		else { $this->linemaxfontsize = $maxfontsize; }

		// Get PAGEBREAK TO TEST for height including the bottom border/padding
		$check_h = max($this->divheight,$lineHeight);

		// This fixes a proven bug...
		if ($endofblock && $newblock && $blockstate==0 && !$content) {  $check_h = 0; }
		// but ? needs to fix potentially more widespread...
	//	if (!$content) {  $check_h = 0; }

		if ($this->blklvl > 0 && !$is_table) {
		   if ($endofblock && $blockstate > 1) {
			if ($this->blk[$this->blklvl]['page_break_after_avoid']) {  $check_h += $lineHeight; }
			$check_h += ($this->blk[$this->blklvl]['padding_bottom'] + $this->blk[$this->blklvl]['border_bottom']['w']);
		   }
		   //	mPDF 5.4.03
		   if (($newblock && ($blockstate==1 || $blockstate==3) && $lineCount == 0) || ($endofblock && $blockstate ==3 && $lineCount == 0)) {
			$check_h += ($this->blk[$this->blklvl]['padding_top'] + $this->blk[$this->blklvl]['margin_top'] + $this->blk[$this->blklvl]['border_top']['w']);
		   }
		}

		// Force PAGE break if column height cannot take check-height
		if ($this->ColActive && $check_h > ($this->PageBreakTrigger - $this->y0)) {
			$this->SetCol($this->NbCol-1);
		}
 
		//	mPDF 5.4.04
		// Avoid just border/background-color moved on to next page
		if ($endofblock && $blockstate > 1 && !$content) { $buff = $this->margBuffer; }
		else { $buff = 0; }


		// PAGEBREAK
		// mPDF 5.4.04
		if(!$is_table && ($this->y+$check_h) > ($this->PageBreakTrigger + $buff) and !$this->InFooter and $this->AcceptPageBreak()) {
  	     		$bak_x=$this->x;//Current X position
			// WORD SPACING
			$ws=$this->ws;//Word Spacing
			$charspacing=$this->charspacing;//Character Spacing
			$this->ResetSpacing();

		      $this->AddPage($this->CurOrientation);

		      $this->x=$bak_x;
			// Added to correct for OddEven Margins
			$currentx += $this->MarginCorrection;
			$this->x += $this->MarginCorrection;

			// WORD SPACING
			$this->SetSpacing($charspacing,$ws);
		}

		if ($this->keep_block_together && !$is_table && $this->kt_p00 < $this->page && ($this->y+$check_h) > $this->kt_y00) {
			$this->printdivbuffer();
			$this->keep_block_together = 0;
		}


		// TOP MARGIN
		if ($newblock && ($blockstate==1 || $blockstate==3) && ($this->blk[$this->blklvl]['margin_top']) && $lineCount == 0 && !$is_table && !$is_list) {
			$this->DivLn($this->blk[$this->blklvl]['margin_top'],$this->blklvl-1,true,$this->blk[$this->blklvl]['margin_collapse']);
		}

		if ($newblock && ($blockstate==1 || $blockstate==3) && $lineCount == 0 && !$is_table && !$is_list) {
			$this->blk[$this->blklvl]['y0'] = $this->y;
			$this->blk[$this->blklvl]['startpage'] = $this->page;
		}

	// ADDED for Paragraph_indent
	$WidthCorrection = 0;
	if (($newblock) && ($blockstate==1 || $blockstate==3) && isset($this->blk[$this->blklvl]['text_indent']) && ($lineCount == 0) && (!$is_table) && (!$is_list) && ($align != 'C')) {
		$ti = $this->ConvertSize($this->blk[$this->blklvl]['text_indent'],$this->blk[$this->blklvl]['inner_width'],$this->FontSize,false);
		$WidthCorrection = ($ti*_MPDFK);
	}


	// PADDING and BORDER spacing/fill
	if (($newblock) && ($blockstate==1 || $blockstate==3) && (($this->blk[$this->blklvl]['padding_top']) || ($this->blk[$this->blklvl]['border_top'])) && ($lineCount == 0) && (!$is_table) && (!$is_list)) {
			// $state = 0 normal; 1 top; 2 bottom; 3 top and bottom
			$this->DivLn($this->blk[$this->blklvl]['padding_top'] + $this->blk[$this->blklvl]['border_top']['w'],-3,true,false,1);
			$this->x = $currentx;
	}


	// Added mPDF 3.0 Float DIV
	$fpaddingR = 0;
	$fpaddingL = 0;

	$usey = $this->y + 0.002;
	if (($newblock) && ($blockstate==1 || $blockstate==3) && ($lineCount == 0) ) {
		$usey += $this->blk[$this->blklvl]['margin_top'] + $this->blk[$this->blklvl]['padding_top'] + $this->blk[$this->blklvl]['border_top']['w'];
	}

	if ($content) {

		// In FinishFlowing Block no lines are justified as it is always last line
		// but if orphansAllowed have allowed content width to go over max width, use J charspacing to compress line
		// JUSTIFICATION J - NOT!
		$nb_carac = 0;
		$nb_spaces = 0;
		$jcharspacing = 0;
		$jws = 0;
		$inclCursive=false;
		foreach ( $content as $k => $chunk ) {
			if (!isset($this->objectbuffer[$k]) || (isset($this->objectbuffer[$k]) && !$this->objectbuffer[$k])) {
				if ($this->usingCoreFont) {
				      $chunk = str_replace(chr(160),chr(32),$chunk );
				}
				else {
				      $chunk = str_replace(chr(194).chr(160),chr(32),$chunk );
				}
				$nb_carac += mb_strlen( $chunk, $this->mb_enc );
				$nb_spaces += mb_substr_count( $chunk,' ', $this->mb_enc );
				if ($checkCursive) {
				}
			}
		}
		// if it's justified, we need to find the char/word spacing (or if orphans have allowed length of line to go over the maxwidth)
		// If "orphans" in fact is just a final space - ignore this
		if (((($contentWidth + $lastitalic) > $maxWidth) && ($content[count($content)-1] != ' ') )  ||
			(!$endofblock && $align=='J' && ($next=='image' || $next=='select' || $next=='input' || $next=='textarea' || ($next=='br' && $this->justifyB4br)))
			) {
 		  // WORD SPACING
			list($jcharspacing,$jws) = $this->GetJspacing($nb_carac,$nb_spaces,($maxWidth-$lastitalic-$contentWidth-$WidthCorrection-(($this->cMarginL+$this->cMarginR)*_MPDFK)-($paddingL+$paddingR +(($fpaddingL + $fpaddingR) * _MPDFK) )),$inclCursive);
		}
		// Check if will fit at word/char spacing of previous line - if so continue it
		// but only allow a maximum of $this->jSmaxWordLast and $this->jSmaxCharLast
		else if ($contentWidth < ($maxWidth - $lastitalic-$WidthCorrection - (($this->cMarginL+$this->cMarginR)* _MPDFK) - ($paddingL+$paddingR +(($fpaddingL + $fpaddingR) * _MPDFK))) && !$this->fixedlSpacing) {
			if ($this->ws > $this->jSmaxWordLast) {
				$jws = $this->jSmaxWordLast;
			}
			if ($this->charspacing > $this->jSmaxCharLast) {
				$jcharspacing = $this->jSmaxCharLast;
			}
			$check = $maxWidth - $lastitalic-$WidthCorrection - $contentWidth - (($this->cMarginL+$this->cMarginR)* _MPDFK) - ($paddingL+$paddingR +(($fpaddingL + $fpaddingR) * _MPDFK) ) - ( $jcharspacing * $nb_carac) - ( $jws * $nb_spaces);
			if ($check <= 0) {
				$jcharspacing = 0;
				$jws = 0;
			}
		}

		$empty = $maxWidth - $lastitalic-$WidthCorrection - $contentWidth - (($this->cMarginL+$this->cMarginR)* _MPDFK) - ($paddingL+$paddingR +(($fpaddingL + $fpaddingR) * _MPDFK) );

		$empty -= ($jcharspacing * $nb_carac);
		$empty -= ($jws * $nb_spaces);

		$empty /= _MPDFK;

		if (!$is_table) {
			$this->maxPosR = max($this->maxPosR , ($this->w - $this->rMargin - $this->blk[$this->blklvl]['outer_right_margin'] - $empty));
			$this->maxPosL = min($this->maxPosL , ($this->lMargin + $this->blk[$this->blklvl]['outer_left_margin'] + $empty));
		}

		$arraysize = count($content);

		$margins = ($this->cMarginL+$this->cMarginR) + ($ipaddingL+$ipaddingR + $fpaddingR + $fpaddingR );

		if (!$is_table) { $this->DivLn($lineHeight,$this->blklvl,false); }	// false -> don't advance y

		// DIRECTIONALITY RTL
		$all_rtl = false;
		$contains_rtl = false;

		$this->x = $currentx + $this->cMarginL + $ipaddingL + $fpaddingL;
		if ($align == 'R') { $this->x += $empty; }
		else if ($align == 'J' && $blockdir == 'rtl') { $this->x += $empty; }
		else if ($align == 'C') { $this->x += ($empty / 2); }


		// Paragraph INDENT
		$WidthCorrection = 0;
		if (($newblock) && ($blockstate==1 || $blockstate==3) && isset($this->blk[$this->blklvl]['text_indent']) && ($lineCount == 0) && (!$is_table) && (!$is_list) && ($align !='C')) {
			$ti = $this->ConvertSize($this->blk[$this->blklvl]['text_indent'],$this->blk[$this->blklvl]['inner_width'],$this->FontSize,false);
			$this->x += $ti;
		}


          foreach ( $content as $k => $chunk )
          {

		// FOR IMAGES
		if ((($blockdir == 'rtl') && ($contains_rtl )) || $all_rtl ) { $dirk = $arraysize-1 - $k; } else { $dirk = $k; }

		$va = 'M';	// default for text
		if (isset($this->objectbuffer[$dirk]) && $this->objectbuffer[$dirk]) {
			$xadj = $this->x - $this->objectbuffer[$dirk]['OUTER-X'];
			$this->objectbuffer[$dirk]['OUTER-X'] += $xadj;
			$this->objectbuffer[$dirk]['BORDER-X'] += $xadj;
			$this->objectbuffer[$dirk]['INNER-X'] += $xadj;
			$va = $this->objectbuffer[$dirk]['vertical-align'];
			$yadj = $this->y - $this->objectbuffer[$dirk]['OUTER-Y'];
			if ($va == 'BS') {
				$yadj += $af + ($this->linemaxfontsize * (0.5 + $this->baselineC)) - $this->objectbuffer[$dirk]['OUTER-HEIGHT'];
			}
			else if ($va == 'M' || $va == '') {
				$yadj += $af + ($this->linemaxfontsize /2) - ($this->objectbuffer[$dirk]['OUTER-HEIGHT']/2);
			}
			else if ($va == 'TB') {
				$yadj += $af + $this->linemaxfontsize - $this->objectbuffer[$dirk]['OUTER-HEIGHT'];
			}
			else if ($va == 'TT') {
				$yadj += $af;
			}
			else if ($va == 'B') {
				$yadj += $af + $this->linemaxfontsize + $bf - $this->objectbuffer[$dirk]['OUTER-HEIGHT'];
			}
			else if ($va == 'T') {
				$yadj += 0;
			}
			$this->objectbuffer[$dirk]['OUTER-Y'] += $yadj;
			$this->objectbuffer[$dirk]['BORDER-Y'] += $yadj;
			$this->objectbuffer[$dirk]['INNER-Y'] += $yadj;
		}



			// DIRECTIONALITY RTL
			if ((($blockdir == 'rtl') && ($contains_rtl )) || $all_rtl ) { $this->restoreFont( $font[ $arraysize-1 - $k ] ); }
			else { $this->restoreFont( $font[ $k ] ); }

			$this->SetSpacing(($this->fixedlSpacing*_MPDFK)+$jcharspacing,($this->fixedlSpacing+$this->minwSpacing)*_MPDFK+$jws);
			$this->fixedlSpacing = false;
			$this->minwSpacing = 0;

	 		// *********** SPAN BACKGROUND COLOR ***************** //
			if (isset($this->spanbgcolor) && $this->spanbgcolor) {
				$cor = $this->spanbgcolorarray;
				$this->SetFColor($cor);
				$save_fill = $fill; $spanfill = 1; $fill = 1;
			}
			if (!empty($this->spanborddet)) {
				if (strpos($contentB[$k],'L')!==false && isset($this->spanborddet['L'])) $this->x += $this->spanborddet['L']['w'];
				if (strpos($contentB[$k],'L')===false) $this->spanborddet['L']['s'] = $this->spanborddet['L']['w'] = 0;
				if (strpos($contentB[$k],'R')===false) $this->spanborddet['R']['s'] = $this->spanborddet['R']['w'] = 0;
			}
			// WORD SPACING
		      $stringWidth = $this->GetStringWidth($chunk ) + ( $this->charspacing * mb_strlen($chunk,$this->mb_enc ) / _MPDFK )
				+ ( $this->ws * mb_substr_count($chunk,' ',$this->mb_enc ) / _MPDFK );
			if (isset($this->objectbuffer[$dirk])) {
				if ($this->objectbuffer[$dirk]['type']=='dottab') {
					$this->objectbuffer[$dirk]['OUTER-WIDTH'] +=$empty;
				}
				$stringWidth = $this->objectbuffer[$dirk]['OUTER-WIDTH'];
			}

			if ($stringWidth==0) { $stringWidth = 0.000001; }
			if ($k == $arraysize-1) $this->Cell( $stringWidth, $lineHeight, $chunk, '', 1, '', $fill, $this->HREF , $currentx,0,0,'M', $fill, $af, $bf, true ); //mono-style line or last part (skips line)
			else $this->Cell( $stringWidth, $lineHeight, $chunk, '', 0, '', $fill, $this->HREF, 0, 0,0,'M', $fill, $af, $bf, true );//first or middle part

			if (!empty($this->spanborddet)) {
				if (strpos($contentB[$k],'R')!==false && $k != $arraysize-1)  $this->x += $this->spanborddet['R']['w'];
			}
	 		// *********** SPAN BACKGROUND COLOR OFF - RESET BLOCK BGCOLOR ***************** //
			if (isset($spanfill) && $spanfill) {
				$fill = $save_fill; $spanfill = 0;
				if ($fill) { $this->SetFColor($bcor); }
			}
          }

	$this->printobjectbuffer($is_table, $blockdir);


	$this->objectbuffer = array();


	$this->ResetSpacing();

	// LIST BULLETS/NUMBERS
	if ($is_list && is_array($this->bulletarray) && ($lineCount == 0) ) {
	  
	  $savedFont = $this->saveFont();

	  $bull = $this->bulletarray;
	  if (isset($bull['level']) && isset($bull['occur']) && isset($this->InlineProperties['LIST'][$bull['level']][$bull['occur']])) {
		$this->restoreInlineProperties($this->InlineProperties['LIST'][$bull['level']][$bull['occur']]);
	  }
	  if (isset($bull['level']) && isset($bull['occur']) && isset($bull['num']) && isset($this->InlineProperties['LISTITEM'][$bull['level']][$bull['occur']][$bull['num']]) && $this->InlineProperties['LISTITEM'][$bull['level']][$bull['occur']][$bull['num']]) { $this->restoreInlineProperties($this->InlineProperties['LISTITEM'][$bull['level']][$bull['occur']][$bull['num']]); }
	  if (isset($bull['font']) && $bull['font'] == 'czapfdingbats') {
		$this->bullet = true;
		$this->SetFont('czapfdingbats','',$this->FontSizePt/2.5);
	  }
	  else { $this->SetFont($this->FontFamily,$this->FontStyle,$this->FontSizePt,true,true); }	// force output
       //Output bullet
	  $this->x = $currentx;
	  if (isset($bull['x'])) { $this->x += $bull['x']; }
	  $this->y -= $lineHeight;
	  if (is_array($bull['col'])) { $this->SetTColor($bull['col']); }

        if (isset($bull['txt'])) { $this->Cell($bull['w'], $lineHeight,$bull['txt'],'','',$bull['align'],0,'',0,-$this->cMarginL, -$this->cMarginR ); }
	  if (isset($bull['font']) && $bull['font'] == 'czapfdingbats') {
		$this->bullet = false;
	  }
	  $this->x = $currentx;	// Reset
	  $this->y += $lineHeight;


	   $this->restoreFont( $savedFont );
	  // $font = array( $savedFont );

	  $this->bulletarray = array();	// prevents repeat of bullet/number if <li>....<br />.....</li>
	}


	}	// END IF CONTENT



	// PADDING and BORDER spacing/fill
	if ($endofblock && ($blockstate > 1) && ($this->blk[$this->blklvl]['padding_bottom'] || $this->blk[$this->blklvl]['border_bottom'] || $this->blk[$this->blklvl]['css_set_height']) && (!$is_table) && (!$is_list)) {
			// If CSS height set, extend bottom - if on same page as block started, and CSS HEIGHT > actual height,
			//	and does not force pagebreak
			$extra = 0;
			if ($this->blk[$this->blklvl]['css_set_height'] && $this->blk[$this->blklvl]['startpage']==$this->page) {
				// predicted height
				$h1 = ($this->y-$this->blk[$this->blklvl]['y0']) + $this->blk[$this->blklvl]['padding_bottom'] + $this->blk[$this->blklvl]['border_bottom']['w'];
				if ($h1 < ($this->blk[$this->blklvl]['css_set_height']+$this->blk[$this->blklvl]['padding_bottom']+$this->blk[$this->blklvl]['padding_top'])) { $extra = ($this->blk[$this->blklvl]['css_set_height']+$this->blk[$this->blklvl]['padding_bottom']+$this->blk[$this->blklvl]['padding_top']) - $h1; }
				if($this->y + $this->blk[$this->blklvl]['padding_bottom'] + $this->blk[$this->blklvl]['border_bottom']['w'] + $extra > $this->PageBreakTrigger) {
					$extra = $this->PageBreakTrigger - ($this->y + $this->blk[$this->blklvl]['padding_bottom'] + $this->blk[$this->blklvl]['border_bottom']['w']);
				}
			}

			// $state = 0 normal; 1 top; 2 bottom; 3 top and bottom
			$this->DivLn($this->blk[$this->blklvl]['padding_bottom'] + $this->blk[$this->blklvl]['border_bottom']['w'] + $extra,-3,true,false,2);
			$this->x = $currentx;


	}

	// SET Bottom y1 of block (used for painting borders)
	if (($endofblock) && ($blockstate > 1) && (!$is_table) && (!$is_list)) {
		$this->blk[$this->blklvl]['y1'] = $this->y;
	}

	// BOTTOM MARGIN
	if (($endofblock) && ($blockstate > 1) && ($this->blk[$this->blklvl]['margin_bottom']) && (!$is_table) && (!$is_list)) {
		if($this->y+$this->blk[$this->blklvl]['margin_bottom'] < $this->PageBreakTrigger and !$this->InFooter) {
		  $this->DivLn($this->blk[$this->blklvl]['margin_bottom'],$this->blklvl-1,true,$this->blk[$this->blklvl]['margin_collapse']);
		}
	}

	// Reset lineheight
	$lineHeight = $this->divheight;
}





function printobjectbuffer($is_table=false, $blockdir=false) {
		if (!$blockdir) { $blockdir = $this->directionality; }
		if ($is_table && $this->shrin_k > 1) { $k = $this->shrin_k; }
		else { $k = 1; }
		$save_y = $this->y;
		$save_x = $this->x;
		$save_currentfontfamily = $this->FontFamily;
		$save_currentfontsize = $this->FontSizePt;
		$save_currentfontstyle = $this->FontStyle.($this->U ? 'U' : '').($this->S ? 'S' : '');
		if ($blockdir == 'rtl') { $rtlalign = 'R'; } else { $rtlalign = 'L'; }
		foreach ($this->objectbuffer AS $ib => $objattr) {
		   if ($objattr['type'] == 'bookmark' || $objattr['type'] == 'indexentry' || $objattr['type'] == 'toc') {
			$x = $objattr['OUTER-X'];
			$y = $objattr['OUTER-Y'];
			$this->y = $y - $this->FontSize/2;
			$this->x = $x;
		   }
		   else {
			$y = $objattr['OUTER-Y'];
			$x = $objattr['OUTER-X'];
			$w = $objattr['OUTER-WIDTH'];
			$h = $objattr['OUTER-HEIGHT'];
			if (isset($objattr['text'])) { $texto = $objattr['text']; }
			$this->y = $y;
			$this->x = $x;
			if (isset($objattr['fontfamily'])) { $this->SetFont($objattr['fontfamily'],'',$objattr['fontsize'] ); }
		   }

		// HR
		   if ($objattr['type'] == 'hr') {
			$this->SetDColor($objattr['color']);
			switch($objattr['align']) {
      		    case 'C':
      		        $empty = $objattr['OUTER-WIDTH'] - $objattr['INNER-WIDTH'];
      		        $empty /= 2;
      		        $x += $empty;
     		        	  break;
      		    case 'R':
      		        $empty = $objattr['OUTER-WIDTH'] - $objattr['INNER-WIDTH'];
      		        $x += $empty;
      		        break;
			}
      		$oldlinewidth = $this->LineWidth;
			$this->SetLineWidth($objattr['linewidth']/$k );
			$this->y += ($objattr['linewidth']/2) + $objattr['margin_top']/$k;
			$this->Line($x,$this->y,$x+$objattr['INNER-WIDTH'],$this->y);
			$this->SetLineWidth($oldlinewidth);
			$this->SetDColor($this->ConvertColor(0));
		   }
		// IMAGE
		   if ($objattr['type'] == 'image') {
			// mPDF 5.6.01  - LAYERS
			if (isset($objattr['z-index']) && $objattr['z-index'] > 0 && $this->currentlayer==0) {
				$this->BeginLayer($objattr['z-index']);
			}
			if(isset($objattr['visibility']) && $objattr['visibility']!='visible' && $objattr['visibility']) {
				$this->SetVisibility($objattr['visibility']);
			}
			if (isset($objattr['opacity'])) { $this->SetAlpha($objattr['opacity']); }
			$rotate = 0;
			$obiw = $objattr['INNER-WIDTH'];
			$obih = $objattr['INNER-HEIGHT'];
			$sx = $objattr['INNER-WIDTH']*_MPDFK / $objattr['orig_w'];
			$sy = abs($objattr['INNER-HEIGHT'])*_MPDFK / abs($objattr['orig_h']);
			$sx = ($objattr['INNER-WIDTH']*_MPDFK / $objattr['orig_w']);
			$sy = ($objattr['INNER-HEIGHT']*_MPDFK / $objattr['orig_h']);

			if (isset($objattr['ROTATE'])) { $rotate = $objattr['ROTATE']; }
			if ($rotate==90) {
				// Clockwise
				$obiw = $objattr['INNER-HEIGHT'];
				$obih = $objattr['INNER-WIDTH'];
				$tr = $this->transformTranslate(0, -$objattr['INNER-WIDTH'], true) ;
				$tr .= ' '. $this->transformRotate(90, $objattr['INNER-X'],($objattr['INNER-Y'] +$objattr['INNER-WIDTH'] ),true) ;
				$sx = $obiw*_MPDFK / $objattr['orig_h'];
				$sy = $obih*_MPDFK / $objattr['orig_w'];
			}
			else if ($rotate==-90 || $rotate==270) {
				// AntiClockwise
				$obiw = $objattr['INNER-HEIGHT'];
				$obih = $objattr['INNER-WIDTH'];
				$tr = $this->transformTranslate($objattr['INNER-WIDTH'], ($objattr['INNER-HEIGHT']-$objattr['INNER-WIDTH']), true) ;
				$tr .= ' '. $this->transformRotate(-90, $objattr['INNER-X'],($objattr['INNER-Y'] +$objattr['INNER-WIDTH'] ),true) ;
				$sx = $obiw*_MPDFK / $objattr['orig_h'];
				$sy = $obih*_MPDFK / $objattr['orig_w'];
			}
			else if ($rotate==180) {
				// Mirror
				$tr = $this->transformTranslate($objattr['INNER-WIDTH'], -$objattr['INNER-HEIGHT'], true) ;
				$tr .= ' '. $this->transformRotate(180, $objattr['INNER-X'],($objattr['INNER-Y'] +$objattr['INNER-HEIGHT'] ),true) ;
			}
			else { $tr = ''; }
			$tr = trim($tr);
			if ($tr) { $tr .= ' '; }
			$gradmask = '';


			if (isset($objattr['itype']) && $objattr['itype']=='svg') {
				$outstring = sprintf('q '.$tr.'%.3F 0 0 %.3F %.3F %.3F cm /FO%d Do Q', $sx, -$sy, $objattr['INNER-X']*_MPDFK-$sx*$objattr['wmf_x'], (($this->h-$objattr['INNER-Y'])*_MPDFK)+$sy*$objattr['wmf_y'], $objattr['ID']);
			}
			else {
				$outstring = sprintf("q ".$tr."%.3F 0 0 %.3F %.3F %.3F cm ".$gradmask."/I%d Do Q",$obiw*_MPDFK, $obih*_MPDFK, $objattr['INNER-X'] *_MPDFK, ($this->h-($objattr['INNER-Y'] +$obih ))*_MPDFK,$objattr['ID'] );
			}
			$this->_out($outstring);
			// LINK
			if (isset($objattr['link'])) $this->Link($objattr['INNER-X'],$objattr['INNER-Y'],$objattr['INNER-WIDTH'],$objattr['INNER-HEIGHT'],$objattr['link']);
			if (isset($objattr['opacity'])) { $this->SetAlpha(1); }
			if ((isset($objattr['border_top']) && $objattr['border_top']>0) || (isset($objattr['border_left']) && $objattr['border_left']>0) || (isset($objattr['border_right']) && $objattr['border_right']>0) || (isset($objattr['border_bottom']) && $objattr['border_bottom']>0)) { $this->PaintImgBorder($objattr,$is_table); }
			if(isset($objattr['visibility']) && $objattr['visibility']!='visible' && $objattr['visibility']) {
				$this->SetVisibility('visible');
			}
			// mPDF 5.6.01  - LAYERS
			if (isset($objattr['z-index']) && $objattr['z-index'] > 0 && $this->currentlayer==0) {
				$this->EndLayer();
			}

		   }


		// TEXT CIRCLE
		   if ($objattr['type'] == 'textcircle') {
			$bgcol = '';	// mPDF 5.5.14
			if (isset($objattr['bgcolor']) && $objattr['bgcolor']) {
				$bgcol = $objattr['bgcolor'];
			}
			$col = $this->ConvertColor(0);
			if (isset($objattr['color']) && $objattr['color']) {
				$col = $objattr['color'];
			}
			$this->SetTColor($col);
			$this->SetFColor($bgcol);
	 		if ($bgcol) $this->Rect($objattr['BORDER-X'], $objattr['BORDER-Y'], $objattr['BORDER-WIDTH'], $objattr['BORDER-HEIGHT'], 'F');	// mPDF 5.5.14
			$this->SetFColor($this->ConvertColor(255));
			if (isset($objattr['BORDER-WIDTH'])) { $this->PaintImgBorder($objattr,$is_table); }
			if (!class_exists('directw', false)) { include(_MPDF_PATH.'classes/directw.php'); }
			if (empty($this->directw)) { $this->directw = new directw($this); }
			$save_lmfs = $this->linemaxfontsize;
			$this->linemaxfontsize = 0;
			if (isset($objattr['top-text'])) {
				$this->directw->CircularText($objattr['INNER-X']+$objattr['INNER-WIDTH']/2, $objattr['INNER-Y']+$objattr['INNER-HEIGHT']/2, $objattr['r']/$k, $objattr['top-text'], 'top', $objattr['fontfamily'], $objattr['fontsize']/$k, $objattr['fontstyle'], $objattr['space-width'], $objattr['char-width'],  $objattr['divider']);		// mPDF 5.5.23
			}
			if (isset($objattr['bottom-text'])) {
				$this->directw->CircularText($objattr['INNER-X']+$objattr['INNER-WIDTH']/2, $objattr['INNER-Y']+$objattr['INNER-HEIGHT']/2, $objattr['r']/$k, $objattr['bottom-text'], 'bottom', $objattr['fontfamily'], $objattr['fontsize']/$k, $objattr['fontstyle'], $objattr['space-width'], $objattr['char-width'],  $objattr['divider']);		// mPDF 5.5.23
			}
			$this->linemaxfontsize = $save_lmfs;
		   }

		   $this->ResetSpacing();

		// DOT-TAB
		   if ($objattr['type'] == 'dottab') {
				$sp = $this->GetStringWidth(' ');
				$nb=floor(($w-2*$sp)/$this->GetStringWidth('.'));
				if ($nb>0) { $dots=' '.str_repeat('.',$nb).' '; }
				else { $dots=' '; }
				$col = $this->ConvertColor(0);
				if (isset($objattr['colorarray']) && is_array($objattr['colorarray'])) {
					$col = $objattr['colorarray'];
				}
				$this->SetTColor($col);
				$save_sbd = $this->spanborddet;
				$save_u = $this->U;
				$save_s = $this->strike;
				$this->spanborddet = '';
				$this->U = false;
				$this->strike = false;
				$this->Cell($w,$h,$dots,0,0,'C');
				$this->spanborddet = $save_sbd;
				$this->U = $save_u;
				$this->strike = $save_s;
				// mPDF 5.0
				$this->SetTColor($this->ConvertColor(0));
		   }

		}
		$this->SetFont($save_currentfontfamily,$save_currentfontstyle,$save_currentfontsize);
		$this->y = $save_y;
		$this->x = $save_x;
		unset($content);
}


function WriteFlowingBlock( $s)
{
	$currentx = $this->x;
	$is_table = $this->flowingBlockAttr[ 'is_table' ];
	$is_list = $this->flowingBlockAttr[ 'is_list' ];
	// width of all the content so far in points
	$contentWidth =& $this->flowingBlockAttr[ 'contentWidth' ];
	// cell width in points
	$maxWidth =& $this->flowingBlockAttr[ 'width' ];
	$lineCount =& $this->flowingBlockAttr[ 'lineCount' ];
	// line height in user units
	$lineHeight =& $this->flowingBlockAttr[ 'height' ];
	$align =& $this->flowingBlockAttr[ 'align' ];
	$content =& $this->flowingBlockAttr[ 'content' ];
	$contentB =& $this->flowingBlockAttr[ 'contentB' ];
	$font =& $this->flowingBlockAttr[ 'font' ];
	$valign =& $this->flowingBlockAttr[ 'valign' ];
	$blockstate = $this->flowingBlockAttr[ 'blockstate' ];

	$newblock = $this->flowingBlockAttr[ 'newblock' ];
	$blockdir = $this->flowingBlockAttr['blockdir'];
	// *********** BLOCK BACKGROUND COLOR ***************** //
	if ($this->blk[$this->blklvl]['bgcolor'] && !$is_table) {
		$fill = 0;
	}
	else {
		$this->SetFColor($this->ConvertColor(255));
		$fill = 0;
	}
	$font[] = $this->saveFont();
	$content[] = '';
	$contentB[] = '';
	$currContent =& $content[ count( $content ) - 1 ];
	// where the line should be cutoff if it is to be justified
	$cutoffWidth = $contentWidth;

	$curlyquote = mb_convert_encoding("\xe2\x80\x9e",$this->mb_enc,'UTF-8');
	$curlylowquote = mb_convert_encoding("\xe2\x80\x9d",$this->mb_enc,'UTF-8');
	// mPDF 5.5.07
	$raquote = mb_convert_encoding("\xc2\xbb",$this->mb_enc,'UTF-8');	//&#187; &raquo;
	// mPDF 5.5.20
	$rsquo = mb_convert_encoding("\xe2\x80\x99",$this->mb_enc,'UTF-8');	//&#8217; &rsquo;
	$sbquo = mb_convert_encoding("\xe2\x80\x9a",$this->mb_enc,'UTF-8');	//&#8218; &sbquo;
	$bdquo = mb_convert_encoding("\xe2\x80\xba",$this->mb_enc,'UTF-8');	//&#8222; &bdquo;

	$CJKoverflow = false;

	// COLS
	$oldcolumn = $this->CurrCol;


   if ($is_table) {
	$ipaddingL = 0;
	$ipaddingR = 0;
	$paddingL = 0;
	$paddingR = 0;
	$cpaddingadjustL = 0;
	$cpaddingadjustR = 0;
 	// Added mPDF 3.0
	$fpaddingR = 0;
	$fpaddingL = 0;
  }
   else {
		$ipaddingL = $this->blk[$this->blklvl]['padding_left'];
		$ipaddingR = $this->blk[$this->blklvl]['padding_right'];
		$paddingL = ($ipaddingL * _MPDFK);
		$paddingR = ($ipaddingR * _MPDFK);
		$this->cMarginL =  $this->blk[$this->blklvl]['border_left']['w'];
		$cpaddingadjustL = -$this->cMarginL;
		$this->cMarginR =  $this->blk[$this->blklvl]['border_right']['w'];
		$cpaddingadjustR = -$this->cMarginR;
		// Added mPDF 3.0 Float DIV
		$fpaddingR = 0;
		$fpaddingL = 0;

		$usey = $this->y + 0.002;
		if (($newblock) && ($blockstate==1 || $blockstate==3) && ($lineCount == 0) ) {
			$usey += $this->blk[$this->blklvl]['margin_top'] + $this->blk[$this->blklvl]['padding_top'] + $this->blk[$this->blklvl]['border_top']['w'];
		}
   }	// *TABLES*

     //OBJECTS - IMAGES & FORM Elements (NB has already skipped line/page if required - in printbuffer)
      if (substr($s,0,3) == "\xbb\xa4\xac") { //identifier has been identified!
		$objattr = $this->_getObjAttr($s);
		$h_corr = 0;
		if ($is_table) {	// *TABLES*
			$maximumW = ($maxWidth/_MPDFK) - ($this->cellPaddingL + $this->cMarginL + $this->cellPaddingR + $this->cMarginR); 	// *TABLES*
		}	// *TABLES*
		else {	// *TABLES*
			if (($newblock) && ($blockstate==1 || $blockstate==3) && ($lineCount == 0) && (!$is_table)) { $h_corr = $this->blk[$this->blklvl]['padding_top'] + $this->blk[$this->blklvl]['border_top']['w']; }
			$maximumW = ($maxWidth/_MPDFK) - ($this->blk[$this->blklvl]['padding_left'] + $this->blk[$this->blklvl]['border_left']['w'] + $this->blk[$this->blklvl]['padding_right'] + $this->blk[$this->blklvl]['border_right']['w'] + $fpaddingL + $fpaddingR );
		}	// *TABLES*
		$objattr = $this->inlineObject($objattr['type'],$this->lMargin + $fpaddingL + ($contentWidth/_MPDFK),($this->y + $h_corr), $objattr, $this->lMargin,($contentWidth/_MPDFK),$maximumW,$lineHeight,true,$is_table);

		// SET LINEHEIGHT for this line ================ RESET AT END
		$lineHeight = MAX($lineHeight,$objattr['OUTER-HEIGHT']);
		$this->objectbuffer[count($content)-1] = $objattr;
		// if (isset($objattr['vertical-align'])) { $valign = $objattr['vertical-align']; }
		// else { $valign = ''; }
		$contentWidth += ($objattr['OUTER-WIDTH'] * _MPDFK);
		return;
	}

	$lbw = $rbw = 0;	// Border widths
	if (!empty($this->spanborddet)) {
		if (isset($this->spanborddet['L'])) $lbw = $this->spanborddet['L']['w'];
		if (isset($this->spanborddet['R'])) $rbw = $this->spanborddet['R']['w'];
	}

   if ($this->usingCoreFont) {
	$tmp = strlen( $s );
   }
   else {
	$tmp = mb_strlen( $s, $this->mb_enc );
   }

   $orphs = 0;
   $check = 0;

   // for every character in the string
   for ( $i = 0; $i < $tmp; $i++ )  {
	// extract the current character
	// get the width of the character in points
	if ($this->usingCoreFont) {
       	$c = $s[$i];
		// Soft Hyphens chr(173)
		$cw = ($this->GetCharWidthCore($c) * _MPDFK);
		if ($this->kerning && $this->useKerning && $i > 0) {
			if (isset($this->CurrentFont['kerninfo'][$s[($i-1)]][$c])) {
				$cw += ($this->CurrentFont['kerninfo'][$s[($i-1)]][$c] * $this->FontSizePt / 1000 );
			}
		}
	}
	else {
	      $c = mb_substr($s,$i,1,$this->mb_enc );
		$cw = ($this->GetCharWidthNonCore($c, false) * _MPDFK);
		if ($this->kerning && $this->useKerning && $i > 0) {
	     		$lastc = mb_substr($s,($i-1),1,$this->mb_enc );
			$ulastc = $this->UTF8StringToArray($lastc, false);
			$uc = $this->UTF8StringToArray($c, false);
			if (isset($this->CurrentFont['kerninfo'][$ulastc[0]][$uc[0]])) {
				$cw += ($this->CurrentFont['kerninfo'][$ulastc[0]][$uc[0]] * $this->FontSizePt / 1000 );
			}
		}
	}

	if ($i==0) {
		$cw += $lbw*_MPDFK;
		$contentB[(count($contentB)-1)] .= 'L';
	}
	if ($i==($tmp-1)) {
		$cw += $rbw*_MPDFK;
		$contentB[(count($contentB)-1)] .= 'R';
	}

	if ($c==' ') { $check = 1; }

	// CHECK for ORPHANS
	else if ($c=='.' || $c==',' || $c==')' || $c==']' || $c==';' || $c==':' || $c=='!' || $c=='?'|| $c=='"' || $c==$curlyquote || $c==$curlylowquote || $c==$raquote || $c==$rsquo || $c==$sbquo || $c==$bdquo )  {$check++; }	// mPDF 5.5.07  mPDF 5.5.20
	else { $check = 0; }
 
	// There's an orphan '. ' or ', ' or <sup>32</sup> about to be cut off at the end of line
	if($check==1) {
		$currContent .= $c;
		$cutoffWidth = $contentWidth;
		$contentWidth += $cw;
		continue;
	}
	if(($this->SUP || $this->SUB) && ($orphs < $this->orphansAllowed)) {	// ? disable orphans in table if  borders used
		$currContent .= $c;
		$cutoffWidth = $contentWidth;
		$contentWidth += $cw;
		$orphs++;
		continue;
	}
	else { $orphs = 0; }

	// ADDED for Paragraph_indent
	$WidthCorrection = 0;
	if (($newblock) && ($blockstate==1 || $blockstate==3) && isset($this->blk[$this->blklvl]['text_indent']) && ($lineCount == 0) && (!$is_table) && (!$is_list) && ($align != 'C')) {
		$ti = $this->ConvertSize($this->blk[$this->blklvl]['text_indent'],$this->blk[$this->blklvl]['inner_width'],$this->FontSize,false);
		$WidthCorrection = ($ti*_MPDFK);
	}

	// Added mPDF 3.0 Float DIV
	$fpaddingR = 0;
	$fpaddingL = 0;

	$usey = $this->y + 0.002;
	if (($newblock) && ($blockstate==1 || $blockstate==3) && ($lineCount == 0) ) {
		$usey += $this->blk[$this->blklvl]['margin_top'] + $this->blk[$this->blklvl]['padding_top'] + $this->blk[$this->blklvl]['border_top']['w'];
	}




       // try adding another char
	if (( $contentWidth + $cw > $maxWidth - $WidthCorrection - (($this->cMarginL+$this->cMarginR)*_MPDFK) - ($paddingL+$paddingR +(($fpaddingL + $fpaddingR) * _MPDFK) ) +  0.001))  {// 0.001 is to correct for deviations converting mm=>pts
		// it won't fit, output what we already have
		$lineCount++;

		// contains any content that didn't make it into this print
		$savedContent = '';
		$savedContentB = '';
		$savedFont = array();
		$savedObj = array();

		// cut off and save any partial words at the end of the string
		$words = explode( ' ', $currContent );
		///////////////////
		// HYPHENATION
		$currWord = $words[count($words)-1] ;
		$success = false;

		// if it looks like we didn't finish any words for this chunk
		if ( count( $words ) == 1 ) {
		  // TO correct for error when word too wide for page - but only when one long word from left to right margin
		  if (count($content) == 1 && $currContent != ' ') {

			// mPDF 5.5.10
			$lastchar = mb_substr($words[0],mb_strlen($words[0], $this->mb_enc)-1, 1, $this->mb_enc);
			$lastContent = $words[0];
			$savedFont = $this->saveFont();
			// replace the current content with the cropped version
			$currContent = rtrim( $lastContent );
		  }
		  else {
			// save and crop off the content currently on the stack
			$savedContent = array_pop( $content );
			$savedContentB = $contentB[(count($contentB)-1)];
			$savedFont = array_pop( $font );
			// trim any trailing spaces off the last bit of content
			$currContent =& $content[ count( $content ) - 1 ];
			$currContent = rtrim( $currContent );
		  }
		}
		else {	// otherwise, we need to find which bit to cut off
		  $lastContent = '';
              for ( $w = 0; $w < count( $words ) - 1; $w++) { $lastContent .= $words[ $w ]." "; }
              $savedContent = $words[ count( $words ) - 1 ];
              $savedFont = $this->saveFont();
              // replace the current content with the cropped version
              $currContent = rtrim( $lastContent );
		}
		// CJK - strip CJK space at end of line
		// &#x3000; = \xe3\x80\x80 = CJK space


		if (isset($this->objectbuffer[(count($content)-1)]) && $this->objectbuffer[(count($content)-1)]['type']=='dottab') {
			$savedObj = array_pop( $this->objectbuffer );
			$contentWidth -= ($this->objectbuffer[(count($content)-1)]['OUTER-WIDTH'] * _MPDFK);
		}

		// Set Current lineheight (correction factor)
		$lhfixed = false;
		if ($is_list) {
			if (preg_match('/([0-9.,]+)mm/',$this->list_lineheight[$this->listlvl][$this->listOcc],$am)) {
				$lhfixed = true;
				$def_fontsize = $this->InlineProperties['LISTITEM'][$this->listlvl][$this->listOcc][$this->listnum]['size'];
				$this->lineheight_correction = $am[1] / $def_fontsize ;
			}
			else {
				$this->lineheight_correction = $this->list_lineheight[$this->listlvl][$this->listOcc];
			}
		}
		else
		if ($is_table) {
			if (preg_match('/([0-9.,]+)mm/',$this->table_lineheight,$am)) {
				$lhfixed = true;
				$def_fontsize = $this->FontSize; 				// needs to be default font-size for block ****
				$this->lineheight_correction = $lineHeight / $def_fontsize ;
			}
			else {
				$this->lineheight_correction = $this->table_lineheight;
			}
		}
		else
		if (isset($this->blk[$this->blklvl]['line_height']) && $this->blk[$this->blklvl]['line_height']) {
			if (preg_match('/([0-9.,]+)mm/',$this->blk[$this->blklvl]['line_height'],$am)) {
				$lhfixed = true;
				$def_fontsize = $this->blk[$this->blklvl]['InlineProperties']['size']; 	// needs to be default font-size for block ****
				$this->lineheight_correction = $am[1] / $def_fontsize ;
			}
			else {
				$this->lineheight_correction = $this->blk[$this->blklvl]['line_height'];
			}
		}
		else {
			$this->lineheight_correction = $this->normalLineheight;
		}
		// update $contentWidth and $cutoffWidth since they changed with cropping
		// Also correct lineheight to maximum fontsize (not for tables)
		$contentWidth = 0;
		//  correct lineheight to maximum fontsize
		if ($lhfixed) { $maxlineHeight = $this->lineheight; }
		else { $maxlineHeight = 0; }
		$this->forceExactLineheight = true;
		$maxfontsize = 0;
		// While we're at it, check for cursive text
		$checkCursive=false;
		foreach ( $content as $k => $chunk )
		{
              $this->restoreFont( $font[ $k ]);
		  if (!isset($this->objectbuffer[$k])) {
			if (!$this->usingCoreFont) {
			      $content[$k] = $chunk = str_replace("\xc2\xad",'',$chunk );
			}
			// Soft Hyphens chr(173)
			else if ($this->FontFamily!='csymbol' && $this->FontFamily!='czapfdingbats') {
			      $content[$k] = $chunk = str_replace(chr(173),'',$chunk );
			}
			$contentWidth += $this->GetStringWidth( $chunk ) * _MPDFK;
			if (!empty($this->spanborddet)) {
				if (strpos($contentB[$k],'L')!==false) $contentWidth += $this->spanborddet['L']['w'] * _MPDFK;
				if (strpos($contentB[$k],'R')!==false) $contentWidth += $this->spanborddet['R']['w'] * _MPDFK;
			}
			if (!$lhfixed) { $maxlineHeight = max($maxlineHeight,$this->FontSize * $this->lineheight_correction ); }
			if ($lhfixed && ($this->FontSize > $def_fontsize || ($this->FontSize > ($lineHeight * $this->lineheight_correction) && $is_list))) {
				$this->forceExactLineheight = false;
			}
			$maxfontsize = max($maxfontsize,$this->FontSize);
		  }
		}

		$lastfontreqstyle = $font[count($font)-1]['ReqFontStyle'];
		$lastfontstyle = $font[count($font)-1]['style'];
		if ($blockdir == 'ltr' && strpos($lastfontreqstyle,"I") !== false && strpos($lastfontstyle,"I") === false) {	// Artificial italic
			$lastitalic = $this->FontSize*0.15*_MPDFK;
		}
		else { $lastitalic = 0; }


		if ($is_list && is_array($this->bulletarray) && $this->bulletarray) {
	  		$actfs = $this->bulletarray['fontsize'];
			if (!$lhfixed) { $maxlineHeight = max($maxlineHeight,$actfs * $this->lineheight_correction );  }
			if ($lhfixed && $actfs > $def_fontsize) { $this->forceExactLineheight = false; }
			$maxfontsize = max($maxfontsize,$actfs);
		}

		// when every text item checked i.e. $maxfontsize is set properly

		$af = 0; 	// Above font
		$bf = 0; 	// Below font
		$mta = 0;	// Maximum top-aligned
		$mba = 0;	// Maximum bottom-aligned

		foreach ( $content as $k => $chunk ) {
		  if (isset($this->objectbuffer[$k]) && $this->objectbuffer[$k]) {
			$contentWidth += $this->objectbuffer[$k]['OUTER-WIDTH'] * _MPDFK;
			$oh = $this->objectbuffer[$k]['OUTER-HEIGHT'];
			$va = $this->objectbuffer[$k]['vertical-align']; // = $objattr['vertical-align'] = set as M,T,B,S
			if ($lhfixed && $oh > $def_fontsize) { $this->forceExactLineheight = false; }

			if ($va == 'BS') {	//  (BASELINE default)
				$af = max($af, ($oh - ($maxfontsize * (0.5 + $this->baselineC))));
			}
			else if ($va == 'M') {
				$af = max($af, ($oh - $maxfontsize)/2);
				$bf = max($bf, ($oh - $maxfontsize)/2);
			}
			else if ($va == 'TT') {
				$bf = max($bf, ($oh - $maxfontsize));
			}
			else if ($va == 'TB') {
				$af = max($af, ($oh - $maxfontsize));
			}
			else if ($va == 'T') {
				$mta = max($mta, $oh);
			}
			else if ($va == 'B') {
				$mba = max($mba, $oh);
			}
		  }
		}
		if ((!$lhfixed || !$this->forceExactLineheight) && ($af > (($maxlineHeight - $maxfontsize)/2) || $bf > (($maxlineHeight - $maxfontsize)/2))) {
			$maxlineHeight = $maxfontsize + $af + $bf;
		}
		else if (!$lhfixed) { $af = $bf = ($maxlineHeight - $maxfontsize)/2; }

		if ($mta > $maxlineHeight) {
			$bf += ($mta - $maxlineHeight);
			$maxlineHeight = $mta;
		}
		if ($mba > $maxlineHeight) {
			$af += ($mba - $maxlineHeight);
			$maxlineHeight = $mba;
		}


		$lineHeight = $maxlineHeight;
		$cutoffWidth = $contentWidth;
		// If NOT images, and maxfontsize NOT > lineHeight - this value determines text baseline positioning
		if ($lhfixed && $af==0 && $bf==0 && $maxfontsize<=($def_fontsize * $this->lineheight_correction * 0.8 )) {
			$this->linemaxfontsize = $def_fontsize;
		}
		else { $this->linemaxfontsize = $maxfontsize; }


		$inclCursive=false;
		foreach ( $content as $k => $chunk ) {
			if (!isset($this->objectbuffer[$k]) || (isset($this->objectbuffer[$k]) && !$this->objectbuffer[$k])) {
				if ($this->usingCoreFont) {
				      $content[$k] = str_replace(chr(160),chr(32),$chunk );
				}
				else {
				      $content[$k] = str_replace(chr(194).chr(160),chr(32),$chunk );
					if ($checkCursive) {
					}
				}
			}
		}

		// JUSTIFICATION J
		$jcharspacing = 0;
		$jws = 0;
		$nb_carac = 0;
		$nb_spaces = 0;
		// if it's justified, we need to find the char/word spacing (or if orphans have allowed length of line to go over the maxwidth)
		if(( $align == 'J' ) || (($cutoffWidth + $lastitalic > $maxWidth - $WidthCorrection - (($this->cMarginL+$this->cMarginR)*_MPDFK) - ($paddingL+$paddingR +(($fpaddingL + $fpaddingR) * _MPDFK) ) +  0.001) && !$CJKoverflow)) {   // 0.001 is to correct for deviations converting mm=>pts
		  // JUSTIFY J (Use character spacing)
 		  // WORD SPACING
			foreach ( $content as $k => $chunk ) {
				if (!isset($this->objectbuffer[$k]) || (isset($this->objectbuffer[$k]) && !$this->objectbuffer[$k])) {
					$nb_carac += mb_strlen( $chunk, $this->mb_enc ) ;
					$nb_spaces += mb_substr_count( $chunk,' ', $this->mb_enc ) ;
				}
			}
			list($jcharspacing,$jws) = $this->GetJspacing($nb_carac,$nb_spaces,($maxWidth-$lastitalic-$cutoffWidth-$WidthCorrection-(($this->cMarginL+$this->cMarginR)*_MPDFK)-($paddingL+$paddingR +(($fpaddingL + $fpaddingR) * _MPDFK) )),$inclCursive);
		}


		// WORD SPACING
		$empty = $maxWidth - $lastitalic-$WidthCorrection - $contentWidth - (($this->cMarginL+$this->cMarginR)* _MPDFK) - ($paddingL+$paddingR +(($fpaddingL + $fpaddingR) * _MPDFK) );

		$empty -= ($jcharspacing * $nb_carac);
		$empty -= ($jws * $nb_spaces);

		$empty /= _MPDFK;
		$b = ''; //do not use borders
		// Get PAGEBREAK TO TEST for height including the top border/padding
		$check_h = max($this->divheight,$lineHeight);
		if (($newblock) && ($blockstate==1 || $blockstate==3) && ($this->blklvl > 0) && ($lineCount == 1) && (!$is_table) && (!$is_list)) {
			$check_h += ($this->blk[$this->blklvl]['padding_top'] + $this->blk[$this->blklvl]['margin_top'] + $this->blk[$this->blklvl]['border_top']['w']);
		}

		if ($this->ColActive && $check_h > ($this->PageBreakTrigger - $this->y0)) {
			$this->SetCol($this->NbCol-1);
		}

		// PAGEBREAK
		// 'If' below used in order to fix "first-line of other page with justify on" bug
		if(!$is_table && ($this->y+$check_h) > $this->PageBreakTrigger and !$this->InFooter and $this->AcceptPageBreak()) {
			$bak_x=$this->x;//Current X position

			// WORD SPACING
			$ws=$this->ws;//Word Spacing
			$charspacing=$this->charspacing;//Character Spacing
			$this->ResetSpacing();

		      $this->AddPage($this->CurOrientation);

		      $this->x = $bak_x;
			// Added to correct for OddEven Margins
			$currentx += $this->MarginCorrection;
			$this->x += $this->MarginCorrection;

			// WORD SPACING
			$this->SetSpacing($charspacing,$ws);
		}

		if ($this->keep_block_together && !$is_table && $this->kt_p00 < $this->page && ($this->y+$check_h) > $this->kt_y00) {
			$this->printdivbuffer();
			$this->keep_block_together = 0;
		}



		// TOP MARGIN
		if (($newblock) && ($blockstate==1 || $blockstate==3) && ($this->blk[$this->blklvl]['margin_top']) && ($lineCount == 1) && (!$is_table) && (!$is_list)) {
			$this->DivLn($this->blk[$this->blklvl]['margin_top'],$this->blklvl-1,true,$this->blk[$this->blklvl]['margin_collapse']);
		}


		// Update y0 for top of block (used to paint border)
		if (($newblock) && ($blockstate==1 || $blockstate==3) && ($lineCount == 1) && (!$is_table) && (!$is_list)) {
			$this->blk[$this->blklvl]['y0'] = $this->y;
			$this->blk[$this->blklvl]['startpage'] = $this->page;
		}

		// TOP PADDING and BORDER spacing/fill
		if (($newblock) && ($blockstate==1 || $blockstate==3) && (($this->blk[$this->blklvl]['padding_top']) || ($this->blk[$this->blklvl]['border_top'])) && ($lineCount == 1) && (!$is_table) && (!$is_list)) {
			// $state = 0 normal; 1 top; 2 bottom; 3 top and bottom
			$this->DivLn($this->blk[$this->blklvl]['padding_top'] + $this->blk[$this->blklvl]['border_top']['w'],-3,true,false,1);
		}

		$arraysize = count($content);

		$margins = ($this->cMarginL+$this->cMarginR) + ($ipaddingL+$ipaddingR + $fpaddingR + $fpaddingR );
 
		// PAINT BACKGROUND FOR THIS LINE
		if (!$is_table) { $this->DivLn($lineHeight,$this->blklvl,false); }	// false -> don't advance y

		$this->x = $currentx + $this->cMarginL + $ipaddingL + $fpaddingL ;
		if ($align == 'R') { $this->x += $empty; }
		else if ($align == 'C') { $this->x += ($empty / 2); }

		// Paragraph INDENT
		if (isset($this->blk[$this->blklvl]['text_indent']) && ($newblock) && ($blockstate==1 || $blockstate==3) && ($lineCount == 1) && (!$is_table) && ($blockdir !='rtl') && ($align !='C')) {
			$ti = $this->ConvertSize($this->blk[$this->blklvl]['text_indent'],$this->blk[$this->blklvl]['inner_width'],$this->FontSize,false);
			$this->x += $ti;
		}

		// DIRECTIONALITY RTL
		$all_rtl = false;
		$contains_rtl = false;

		foreach ( $content as $k => $chunk ) {

			// FOR IMAGES - UPDATE POSITION
			if (($blockdir =='rtl' && $contains_rtl) || $all_rtl) { $dirk = $arraysize-1 - $k ; } else { $dirk = $k; }

			$va = 'M';	// default for text
			if (isset($this->objectbuffer[$dirk]) && $this->objectbuffer[$dirk]) {
			  $xadj = $this->x - $this->objectbuffer[$dirk]['OUTER-X'] ;
			  $this->objectbuffer[$dirk]['OUTER-X'] += $xadj;
			  $this->objectbuffer[$dirk]['BORDER-X'] += $xadj;
			  $this->objectbuffer[$dirk]['INNER-X'] += $xadj;
			  $va = $this->objectbuffer[$dirk]['vertical-align'];
			  $yadj = $this->y - $this->objectbuffer[$dirk]['OUTER-Y'];
			  if ($va == 'BS') {
				$yadj += $af + ($this->linemaxfontsize * (0.5 + $this->baselineC)) - $this->objectbuffer[$dirk]['OUTER-HEIGHT'];
			  }
			  else if ($va == 'M' || $va == '') {
				$yadj += $af + ($this->linemaxfontsize /2) - ($this->objectbuffer[$dirk]['OUTER-HEIGHT']/2);
			  }
			  else if ($va == 'TB') {
				$yadj += $af + $this->linemaxfontsize - $this->objectbuffer[$dirk]['OUTER-HEIGHT'];
			  }
			  else if ($va == 'TT') {
				$yadj += $af;
			  }
			  else if ($va == 'B') {
				$yadj += $af + $this->linemaxfontsize + $bf - $this->objectbuffer[$dirk]['OUTER-HEIGHT'];
			  }
			  else if ($va == 'T') {
				$yadj += 0;
			  }
			  $this->objectbuffer[$dirk]['OUTER-Y'] += $yadj;
			  $this->objectbuffer[$dirk]['BORDER-Y'] += $yadj;
			  $this->objectbuffer[$dirk]['INNER-Y'] += $yadj;
			}

			// DIRECTIONALITY RTL
			if ((($blockdir == 'rtl') && ($contains_rtl )) || ($all_rtl )) { $this->restoreFont($font[$arraysize-1 - $k]); }
			else { $this->restoreFont( $font[ $k ] ); }

			$this->SetSpacing(($this->fixedlSpacing*_MPDFK)+$jcharspacing,($this->fixedlSpacing+$this->minwSpacing)*_MPDFK+$jws);
			// Now unset these values so they don't influence GetStringwidth below or in fn. Cell
			$this->fixedlSpacing = false;
			$this->minwSpacing = 0;

	 		// *********** SPAN BACKGROUND COLOR ***************** //
			if ($this->spanbgcolor) {
				$cor = $this->spanbgcolorarray;
				$this->SetFColor($cor);
				$save_fill = $fill; $spanfill = 1; $fill = 1;
			}
			if (!empty($this->spanborddet)) {
				if (strpos($contentB[$k],'L')!==false) $this->x += $this->spanborddet['L']['w'];
				if (strpos($contentB[$k],'L')===false) $this->spanborddet['L']['s'] = $this->spanborddet['L']['w'] = 0;
				if (strpos($contentB[$k],'R')===false) $this->spanborddet['R']['s'] = $this->spanborddet['R']['w'] = 0;
			}

			// WORD SPACING
		      $stringWidth = $this->GetStringWidth($chunk );
			$stringWidth += ( $this->charspacing * mb_strlen($chunk,$this->mb_enc ) / _MPDFK );
			$stringWidth += ( $this->ws * mb_substr_count($chunk,' ',$this->mb_enc ) / _MPDFK );
			if (isset($this->objectbuffer[$dirk])) { $stringWidth = $this->objectbuffer[$dirk]['OUTER-WIDTH'];  }

			if ($stringWidth==0) { $stringWidth = 0.000001; }
			if ($k == $arraysize-1) {
				$stringWidth -= ( $this->charspacing / _MPDFK );
				$this->Cell( $stringWidth, $lineHeight, $chunk, '', 1, '', $fill, $this->HREF, $currentx,0,0,'M', $fill, $af, $bf, true ); //mono-style line or last part (skips line)
			}
			else $this->Cell( $stringWidth, $lineHeight, $chunk, '', 0, '', $fill, $this->HREF, 0, 0,0,'M', $fill, $af, $bf, true );//first or middle part

			if (!empty($this->spanborddet)) {
				if (strpos($contentB[$k],'R')!==false && $k != $arraysize-1)  $this->x += $this->spanborddet['R']['w'];
			}
	 		// *********** SPAN BACKGROUND COLOR OFF - RESET BLOCK BGCOLOR ***************** //
			if (isset($spanfill) && $spanfill) {
				$fill = $save_fill; $spanfill = 0;
				if ($fill) { $this->SetFColor($bcor); }
			}
		}
		if (!$is_table) {
			$this->maxPosR = max($this->maxPosR , ($this->w - $this->rMargin - $this->blk[$this->blklvl]['outer_right_margin']));
			$this->maxPosL = min($this->maxPosL , ($this->lMargin + $this->blk[$this->blklvl]['outer_left_margin']));
		}

		// move on to the next line, reset variables, tack on saved content and current char

		$this->printobjectbuffer($is_table, $blockdir);
		$this->objectbuffer = array();

		// LIST BULLETS/NUMBERS
		if ($is_list && is_array($this->bulletarray) && ($lineCount == 1) ) {
		  
		  $this->ResetSpacing();

		  $bull = $this->bulletarray;
	  	  if (isset($bull['level']) && isset($bull['occur']) && isset($this->InlineProperties['LIST'][$bull['level']][$bull['occur']])) {
			$this->restoreInlineProperties($this->InlineProperties['LIST'][$bull['level']][$bull['occur']]);
		  }
		  if (isset($bull['level']) && isset($bull['occur']) && isset($bull['num']) && isset($this->InlineProperties['LISTITEM'][$bull['level']][$bull['occur']][$bull['num']]) && $this->InlineProperties['LISTITEM'][$bull['level']][$bull['occur']][$bull['num']]) { $this->restoreInlineProperties($this->InlineProperties['LISTITEM'][$bull['level']][$bull['occur']][$bull['num']]); }
	  	  if (isset($bull['font']) && $bull['font'] == 'czapfdingbats') {
			$this->bullet = true;
			$this->SetFont('czapfdingbats','',$this->FontSizePt/2.5);
		  }
		  else { $this->SetFont($this->FontFamily,$this->FontStyle,$this->FontSizePt,true,true); }	// force output
	        //Output bullet
	  	  $this->x = $currentx;
	 	  if (isset($bull['x'])) { $this->x += $bull['x']; }
		  $this->y -= $lineHeight;
	  	  if (is_array($bull['col'])) { $this->SetTColor($bull['col']); }
		  if (isset($bull['txt'])) { $this->Cell($bull['w'], $lineHeight,$bull['txt'],'','',$bull['align'],0,'',0,-$this->cMarginL, -$this->cMarginR ); }
	  	  if (isset($bull['font']) && $bull['font'] == 'czapfdingbats') {
			$this->bullet = false;
		  }
		  $this->x = $currentx;	// Reset
		  $this->y += $lineHeight;


		  $this->bulletarray = array();	// prevents repeat of bullet/number if <li>....<br />.....</li>
		}



		// Reset lineheight
		$lineHeight = $this->divheight;
		$valign = 'M';

		$this->restoreFont( $savedFont );
		$lbw = $rbw = 0;	// Border widths
		if (!empty($this->spanborddet)) {
			$lbw = $this->spanborddet['L']['w'];
			$rbw = $this->spanborddet['R']['w'];
		}

		$font = array();
		$content = array();
		$contentB = array();
		$contentWidth = 0;
		if (!empty($savedObj)) {
			$this->objectbuffer[] = $savedObj;
			$font[] = $savedFont;
			$content[] = '';
			$contentB[] = '';
			$contentWidth += $savedObj['OUTER-WIDTH'] * _MPDFK;
		}
		$font[] = $savedFont;
		$content[] = $savedContent . $c;
		$contentB[] = $savedContentB ;
		$currContent =& $content[ (count($content)-1) ];
		// CJK - strip CJK space at end of line
		// &#x3000; = \xe3\x80\x80 = CJK space
		$contentWidth += $this->GetStringWidth( $currContent ) * _MPDFK;
		if (strpos($savedContentB,'L')!==false) $contentWidth += $lbw;
		$cutoffWidth = $contentWidth;
		$CJKoverflow = false;
      }
      // another character will fit, so add it on
	else {
		$contentWidth += $cw;
		$currContent .= $c;
	}
    }
    unset($content);
    unset($contentB);
}
//----------------------END OF FLOWING BLOCK------------------------------------//





////////////////////////////////////////////////////////////////////////////////
// ADDED forcewrap - to call from inline OBJECT functions to breakwords if necessary in cell
////////////////////////////////////////////////////////////////////////////////
function WordWrap(&$text, $maxwidth, $forcewrap = 0) {
    $biggestword=0;
    $toonarrow=false;

    $text = trim($text);

    if ($text==='') return 0;
    $space = $this->GetCharWidth(' ',false);
    $lines = explode("\n", $text);
    $text = '';
    $count = 0;
    foreach ($lines as $line) {
	$words = explode(' ', $line);
	$width = 0;
	foreach ($words as $word) {
		$word = trim($word);
		$wordwidth = $this->GetStringWidth($word);
		//Warn user that maxwidth is insufficient
		if ($wordwidth > $maxwidth + 0.0001) {
			if ($wordwidth > $biggestword) { $biggestword = $wordwidth; }
			$toonarrow=true;
			// ADDED
			if ($forcewrap) {
			  while($wordwidth > $maxwidth) {
				$chw = 0;	// check width
				for ( $i = 0; $i < mb_strlen($word, $this->mb_enc ); $i++ ) {
					$chw = $this->GetStringWidth(mb_substr($word,0,$i+1,$this->mb_enc ));
					if ($chw > $maxwidth ) {
						if ($text) {
							$text = rtrim($text)."\n".mb_substr($word,0,$i,$this->mb_enc );
							$count++;
						}
						else {
							$text = mb_substr($word,0,$i,$this->mb_enc );
						}
						$word = mb_substr($word,$i,mb_strlen($word, $this->mb_enc )-$i,$this->mb_enc );
						$wordwidth = $this->GetStringWidth($word);
						$width = $maxwidth;
						break;
					}
				}
			  }
			}
		}

		if ($width + $wordwidth  < $maxwidth - 0.0001) {
			$width += $wordwidth + $space;
			$text .= $word.' ';
		}
		else {
			$width = $wordwidth + $space;
			$text = rtrim($text)."\n".$word.' ';
			$count++;
            }
	}
	$text .= "\n";
	$count++;
    }
    $text = rtrim($text);

    //Return -(wordsize) if word is bigger than maxwidth

	// ADDED
	if ($forcewrap) { return $count; }
      if (($toonarrow) && ($this->table_error_report)) {
		$this->Error("Word is too long to fit in table - ".$this->table_error_report_param);
	}
    if ($toonarrow) return -$biggestword;
    else return $count;
}


function _SetTextRendering($mode) {
	if (!(($mode == 0) || ($mode == 1) || ($mode == 2)))
	$this->Error("Text rendering mode should be 0, 1 or 2 (value : $mode)");
	$tr = ($mode.' Tr');
	if($this->page>0 && ((isset($this->pageoutput[$this->page]['TextRendering']) && $this->pageoutput[$this->page]['TextRendering'] != $tr) || !isset($this->pageoutput[$this->page]['TextRendering']) || $this->keep_block_together)) { $this->_out($tr); }
	$this->pageoutput[$this->page]['TextRendering'] = $tr;

}

function SetTextOutline($width, $col=0) {
  if ($width == false) //Now resets all values
  {
    $this->outline_on = false;
    $this->SetLineWidth(0.2);
    $this->SetDColor($this->ConvertColor(0));
    $this->_SetTextRendering(0);
    $tr = ('0 Tr');
	if($this->page>0 && ((isset($this->pageoutput[$this->page]['TextRendering']) && $this->pageoutput[$this->page]['TextRendering'] != $tr) || !isset($this->pageoutput[$this->page]['TextRendering']) || $this->keep_block_together)) { $this->_out($tr); }
	$this->pageoutput[$this->page]['TextRendering'] = $tr;
  }
  else
  {
    $this->SetLineWidth($width);
    $this->SetDColor($col);
    $tr = ('2 Tr');
	if($this->page>0 && ((isset($this->pageoutput[$this->page]['TextRendering']) && $this->pageoutput[$this->page]['TextRendering'] != $tr) || !isset($this->pageoutput[$this->page]['TextRendering']) || $this->keep_block_together)) { $this->_out($tr); }
	$this->pageoutput[$this->page]['TextRendering'] = $tr;
  }
}

function Image($file,$x,$y,$w=0,$h=0,$type='',$link='',$paint=true, $constrain=true, $watermark=false, $shownoimg=true, $allowvector=true) {
	if(!file_exists($file))
	{
		return false;
	}
	
	$orig_srcpath = $file;
	$this->GetFullPath($file);

	$info=$this->_getImage($file, true, $allowvector, $orig_srcpath );
	if(!$info && $paint) {
		$info = $this->_getImage($this->noImageFile);
		if ($info) {
			$file = $this->noImageFile;
			$w = ($info['w'] * (25.4/$this->dpi)); 	// 14 x 16px
			$h = ($info['h'] * (25.4/$this->dpi)); 	// 14 x 16px
		}
	}
	if(!$info) return false;
	//Automatic width and height calculation if needed
	if($w==0 and $h==0) {
           if ($info['type']=='svg') {
			// returned SVG units are pts
			// divide by k to get user units (mm)
			$w = abs($info['w'])/_MPDFK;
			$h = abs($info['h']) /_MPDFK;
		}
		else {
			//Put image at default image dpi
			$w=($info['w']/_MPDFK) * (72/$this->img_dpi);
			$h=($info['h']/_MPDFK) * (72/$this->img_dpi);
		}
	}
	if($w==0)	$w=abs($h*$info['w']/$info['h']);
	if($h==0)	$h=abs($w*$info['h']/$info['w']);


	if ($constrain) {
	  // Automatically resize to maximum dimensions of page if too large
	  if (isset($this->blk[$this->blklvl]['inner_width']) && $this->blk[$this->blklvl]['inner_width']) { $maxw = $this->blk[$this->blklvl]['inner_width']; }
	  else { $maxw = $this->pgwidth; }
	  if ($w > $maxw) {
		$w = $maxw;
		$h=abs($w*$info['h']/$info['w']);
	  }
	  if ($h > $this->h - ($this->tMargin + $this->bMargin + 1))  {  // see below - +10 to avoid drawing too close to border of page
   		$h = $this->h - ($this->tMargin + $this->bMargin + 1) ;
		if ($this->fullImageHeight) { $h = $this->fullImageHeight; }
		$w=abs($h*$info['w']/$info['h']);
	  }


	  //Avoid drawing out of the paper(exceeding width limits).
	  //if ( ($x + $w) > $this->fw ) {
	  if ( ($x + $w) > $this->w ) {
		$x = $this->lMargin;
		$y += 5;
	  }

	  $changedpage = false;
	  $oldcolumn = $this->CurrCol;
	  //Avoid drawing out of the page.
	  if($y+$h>$this->PageBreakTrigger and !$this->InFooter and $this->AcceptPageBreak()) {
		$this->AddPage($this->CurOrientation);
		// Added to correct for OddEven Margins
		$x=$x +$this->MarginCorrection;
		$y = $tMargin + $this->margin_header;
		$changedpage = true;
	  }
	}	// end of IF constrain

	if ($info['type']=='svg') {
		$sx = $w*_MPDFK / $info['w'];
		$sy = -$h*_MPDFK / $info['h'];
		$outstring = sprintf('q %.3F 0 0 %.3F %.3F %.3F cm /FO%d Do Q', $sx, $sy, $x*_MPDFK-$sx*$info['x'], (($this->h-$y)*_MPDFK)-$sy*$info['y'], $info['i']);
	}
	else {
		$outstring = sprintf("q %.3F 0 0 %.3F %.3F %.3F cm /I%d Do Q",$w*_MPDFK,$h*_MPDFK,$x*_MPDFK,($this->h-($y+$h))*_MPDFK,$info['i']);
	}

	if($paint) {
		$this->_out($outstring);
		if($link) $this->Link($x,$y,$w,$h,$link);

		// Avoid writing text on top of the image. // THIS WAS OUTSIDE THE if ($paint) bit!!!!!!!!!!!!!!!!
		$this->y = $y + $h;
	}

	//Return width-height array
	$sizesarray['WIDTH'] = $w;
	$sizesarray['HEIGHT'] = $h;
	$sizesarray['X'] = $x; //Position before painting image
	$sizesarray['Y'] = $y; //Position before painting image
	$sizesarray['OUTPUT'] = $outstring;

	$sizesarray['IMAGE_ID'] = $info['i'];
	$sizesarray['itype'] = $info['type'];
	$sizesarray['set-dpi'] = $info['set-dpi'];
	return $sizesarray;
}



//=============================================================
//=============================================================
//=============================================================
//=============================================================
//=============================================================

function _getObjAttr($t) {
	$c = explode("\xbb\xa4\xac",$t,2);
	$c = explode(",",$c[1],2);
	foreach($c as $v) {
		$v = explode("=",$v,2);
		$sp[$v[0]] = $v[1];
	}
	return (unserialize($sp['objattr']));
}


function inlineObject($type,$x,$y,$objattr,$Lmargin,$widthUsed,$maxWidth,$lineHeight,$paint=false,$is_table=false)
{
   if ($is_table) { $k = $this->shrin_k; } else { $k = 1; }

   // NB $x is only used when paint=true
	// Lmargin not used
   $w = 0;
   if (isset($objattr['width'])) { $w = $objattr['width']/$k; }
   $h = 0;
   if (isset($objattr['height'])) { $h = abs($objattr['height']/$k); }
   $widthLeft = $maxWidth - $widthUsed;
   $maxHeight = $this->h - ($this->tMargin + $this->bMargin + 10) ;
   if ($this->fullImageHeight) { $maxHeight = $this->fullImageHeight; }
   // For Images
   if (isset($objattr['border_left'])) {
	$extraWidth = ($objattr['border_left']['w'] + $objattr['border_right']['w'] + $objattr['margin_left']+ $objattr['margin_right'])/$k;
	$extraHeight = ($objattr['border_top']['w'] + $objattr['border_bottom']['w'] + $objattr['margin_top']+ $objattr['margin_bottom'])/$k;

	if ($type == 'image' || $type == 'barcode' || $type == 'textcircle') {
		$extraWidth += ($objattr['padding_left'] + $objattr['padding_right'])/$k;
		$extraHeight += ($objattr['padding_top'] + $objattr['padding_bottom'])/$k;
	}
   }

   if (!isset($objattr['vertical-align'])) { $objattr['vertical-align'] = 'M'; }

   if ($type == 'image' || (isset($objattr['subtype']) && $objattr['subtype'] == 'IMAGE')) {
    if (isset($objattr['itype']) && ($objattr['itype'] == 'wmf' || $objattr['itype'] == 'svg')) {
	$file = $objattr['file'];
 	$info=$this->formobjects[$file];
    }
    else if (isset($objattr['file'])) {
	$file = $objattr['file'];
	$info=$this->images[$file];
    }
   }
    if ($type == 'annot' || $type == 'bookmark' || $type == 'indexentry' || $type == 'toc') {
	$w = 0.00001;
	$h = 0.00001;
   }

   // TEST whether need to skipline
   if (!$paint) {
	if ($type == 'hr') {	// always force new line
		if (($y + $h + $lineHeight > $this->PageBreakTrigger) && !$this->InFooter && !$is_table) { return array(-2, $w ,$h ); } // New page + new line
		else { return array(1, $w ,$h ); } // new line
	}
	else {
		if ($widthUsed > 0 && $w > $widthLeft && (!$is_table || $type != 'image')) { 	// New line needed
			if (($y + $h + $lineHeight > $this->PageBreakTrigger) && !$this->InFooter) { return array(-2,$w ,$h ); } // New page + new line
			return array(1,$w ,$h ); // new line
		}
		else if ($widthUsed > 0 && $w > $widthLeft && $is_table) { 	// New line needed in TABLE
			return array(1,$w ,$h ); // new line
		}
		// Will fit on line but NEW PAGE REQUIRED
		else if (($y + $h > $this->PageBreakTrigger) && !$this->InFooter && !$is_table) { return array(-1,$w ,$h ); }
		else { return array(0,$w ,$h ); }
	}
   }

   if ($type == 'annot' || $type == 'bookmark' || $type == 'indexentry' || $type == 'toc') {
	$w = 0.00001;
	$h = 0.00001;
	$objattr['BORDER-WIDTH'] = 0;
	$objattr['BORDER-HEIGHT'] = 0;
	$objattr['BORDER-X'] = $x;
	$objattr['BORDER-Y'] = $y;
	$objattr['INNER-WIDTH'] = 0;
	$objattr['INNER-HEIGHT'] = 0;
	$objattr['INNER-X'] = $x;
	$objattr['INNER-Y'] = $y;
  }

  if ($type == 'image') {
	// Automatically resize to width remaining
	if ($w > $widthLeft  && !$is_table) {
		$w = $widthLeft ;
		$h=abs($w*$info['h']/$info['w']);
	}
	$img_w = $w - $extraWidth ;
	$img_h = $h - $extraHeight ;

	$objattr['BORDER-WIDTH'] = $img_w + $objattr['padding_left']/$k + $objattr['padding_right']/$k + (($objattr['border_left']['w']/$k + $objattr['border_right']['w']/$k)/2) ;
	$objattr['BORDER-HEIGHT'] = $img_h + $objattr['padding_top']/$k + $objattr['padding_bottom']/$k + (($objattr['border_top']['w']/$k + $objattr['border_bottom']['w']/$k)/2) ;
	$objattr['BORDER-X'] = $x + $objattr['margin_left']/$k + (($objattr['border_left']['w']/$k)/2) ;
	$objattr['BORDER-Y'] = $y + $objattr['margin_top']/$k + (($objattr['border_top']['w']/$k)/2) ;
	$objattr['INNER-WIDTH'] = $img_w;
	$objattr['INNER-HEIGHT'] = $img_h;
	$objattr['INNER-X'] = $x + $objattr['padding_left']/$k + $objattr['margin_left']/$k + ($objattr['border_left']['w']/$k);
	$objattr['INNER-Y'] = $y + $objattr['padding_top']/$k + $objattr['margin_top']/$k + ($objattr['border_top']['w']/$k) ;
	$objattr['ID'] = $info['i'];
   }

   if ($type == 'input' && $objattr['subtype'] == 'IMAGE') {
	$img_w = $w - $extraWidth ;
	$img_h = $h - $extraHeight ;
	$objattr['BORDER-WIDTH'] = $img_w + (($objattr['border_left']['w']/$k + $objattr['border_right']['w']/$k)/2) ;
	$objattr['BORDER-HEIGHT'] = $img_h + (($objattr['border_top']['w']/$k + $objattr['border_bottom']['w']/$k)/2) ;
	$objattr['BORDER-X'] = $x + $objattr['margin_left']/$k + (($objattr['border_left']['w']/$k)/2) ;
	$objattr['BORDER-Y'] = $y + $objattr['margin_top']/$k + (($objattr['border_top']['w']/$k)/2) ;
	$objattr['INNER-WIDTH'] = $img_w;
	$objattr['INNER-HEIGHT'] = $img_h;
	$objattr['INNER-X'] = $x + $objattr['margin_left']/$k + ($objattr['border_left']['w']/$k);
	$objattr['INNER-Y'] = $y + $objattr['margin_top']/$k + ($objattr['border_top']['w']/$k) ;
	$objattr['ID'] = $info['i'];
   }

  if ($type == 'barcode' || $type == 'textcircle') {
	$b_w = $w - $extraWidth ;
	$b_h = $h - $extraHeight ;
	$objattr['BORDER-WIDTH'] = $b_w + $objattr['padding_left']/$k + $objattr['padding_right']/$k + (($objattr['border_left']['w']/$k + $objattr['border_right']['w']/$k)/2) ;
	$objattr['BORDER-HEIGHT'] = $b_h + $objattr['padding_top']/$k + $objattr['padding_bottom']/$k + (($objattr['border_top']['w']/$k + $objattr['border_bottom']['w']/$k)/2) ;
	$objattr['BORDER-X'] = $x + $objattr['margin_left']/$k + (($objattr['border_left']['w']/$k)/2) ;
	$objattr['BORDER-Y'] = $y + $objattr['margin_top']/$k + (($objattr['border_top']['w']/$k)/2) ;
	$objattr['INNER-X'] = $x + $objattr['padding_left']/$k + $objattr['margin_left']/$k + ($objattr['border_left']['w']/$k);
	$objattr['INNER-Y'] = $y + $objattr['padding_top']/$k + $objattr['margin_top']/$k + ($objattr['border_top']['w']/$k) ;
	$objattr['INNER-WIDTH'] = $b_w;
	$objattr['INNER-HEIGHT'] = $b_h;
   }


   if ($type == 'textarea') {
	// Automatically resize to width remaining
	if ($w > $widthLeft && !$is_table) {
		$w = $widthLeft ;
	}
	if (($y + $h > $this->PageBreakTrigger) && !$this->InFooter) {
		$h=$this->h - $y - $this->bMargin;
	}
   }

   if ($type == 'hr') {
	if ($is_table) {
		$objattr['INNER-WIDTH'] = $maxWidth * $objattr['W-PERCENT']/100;
		$objattr['width'] = $objattr['INNER-WIDTH'];
		$w = $maxWidth;
	}
	else {
		if ($w>$maxWidth) { $w = $maxWidth; }
		$objattr['INNER-WIDTH'] = $w;
		$w = $maxWidth;
	}
  }



   if (($type == 'select') || ($type == 'input' && ($objattr['subtype'] == 'TEXT' || $objattr['subtype'] == 'PASSWORD'))) {
	// Automatically resize to width remaining
	if ($w > $widthLeft && !$is_table) {
		$w = $widthLeft;
	}
   }

   if ($type == 'textarea' || $type == 'select' || $type == 'input') {
	if (isset($objattr['fontsize'])) $objattr['fontsize'] /= $k;
	if (isset($objattr['linewidth'])) $objattr['linewidth'] /= $k;
   }

   if (!isset($objattr['BORDER-Y'])) { $objattr['BORDER-Y'] = 0; }
   if (!isset($objattr['BORDER-X'])) { $objattr['BORDER-X'] = 0; }
   if (!isset($objattr['INNER-Y'])) { $objattr['INNER-Y'] = 0; }
   if (!isset($objattr['INNER-X'])) { $objattr['INNER-X'] = 0; }

   //Return width-height array
   $objattr['OUTER-WIDTH'] = $w;
   $objattr['OUTER-HEIGHT'] = $h;
   $objattr['OUTER-X'] = $x;
   $objattr['OUTER-Y'] = $y;
   return $objattr;
}


//=============================================================
//=============================================================
//=============================================================
//=============================================================
//=============================================================

function SetLineJoin($mode=0)
{
	$s=sprintf('%d j',$mode);
	if($this->page>0 && ((isset($this->pageoutput[$this->page]['LineJoin']) && $this->pageoutput[$this->page]['LineJoin'] != $s) || !isset($this->pageoutput[$this->page]['LineJoin']) || $this->keep_block_together)) { $this->_out($s); }
	$this->pageoutput[$this->page]['LineJoin'] = $s;

}
function SetLineCap($mode=2) {
	$s=sprintf('%d J',$mode);
	if($this->page>0 && ((isset($this->pageoutput[$this->page]['LineCap']) && $this->pageoutput[$this->page]['LineCap'] != $s) || !isset($this->pageoutput[$this->page]['LineCap']) || $this->keep_block_together)) { $this->_out($s); }
	$this->pageoutput[$this->page]['LineCap'] = $s;

}

function SetDash($black=false,$white=false)
{
        if($black and $white) $s=sprintf('[%.3F %.3F] 0 d',$black*_MPDFK,$white*_MPDFK);
        else $s='[] 0 d';
	if($this->page>0 && ((isset($this->pageoutput[$this->page]['Dash']) && $this->pageoutput[$this->page]['Dash'] != $s) || !isset($this->pageoutput[$this->page]['Dash']) || $this->keep_block_together)) { $this->_out($s); }
	$this->pageoutput[$this->page]['Dash'] = $s;

}

function SetDisplayPreferences($preferences) {
	// String containing any or none of /HideMenubar/HideToolbar/HideWindowUI/DisplayDocTitle/CenterWindow/FitWindow
    $this->DisplayPreferences .= $preferences;
}


function Ln($h='',$collapsible=0)
{
// Added collapsible to allow collapsible top-margin on new page
	//Line feed; default value is last cell height
	$this->x = $this->lMargin + $this->blk[$this->blklvl]['outer_left_margin'];
	if ($collapsible && ($this->y==$this->tMargin) && (!$this->ColActive)) { $h = 0; }
	if(is_string($h)) $this->y+=$this->lasth;
	else $this->y+=$h;
}

// $state = 0 normal; 1 top; 2 bottom; 3 top and bottom
function DivLn($h,$level=-3,$move_y=true,$collapsible=false,$state=0) {
  // this->x is returned as it was
  // adds lines (y) where DIV bgcolors are filled in
  // allows .00001 as nominal height used for bookmarks/annotations etc.
  if ($collapsible && (sprintf("%0.4f", $this->y)==sprintf("%0.4f", $this->tMargin)) && (!$this->ColActive)) { return; }

	// Still use this method if columns or page-break-inside: avoid, as it allows repositioning later
	// otherwise, now uses PaintDivBB()
  if (!$this->ColActive && !$this->keep_block_together && !$this->kwt) {
	if ($move_y && !$this->ColActive) { $this->y += $h; }
	return;
  }

  if ($level == -3) { $level = $this->blklvl; }
  $firstblockfill = $this->GetFirstBlockFill();
  if ($firstblockfill && $this->blklvl > 0 && $this->blklvl >= $firstblockfill) {
	$last_x = 0;
	$last_w = 0;
	$last_fc = $this->FillColor;
	$bak_x = $this->x;
	$bak_h = $this->divheight;
	$this->divheight = 0;	// Temporarily turn off divheight - as Cell() uses it to check for PageBreak
	for ($blvl=$firstblockfill;$blvl<=$level;$blvl++) {
		$this->SetBlockFill($blvl);
		$this->x = $this->lMargin + $this->blk[$blvl]['outer_left_margin'];
		if ($last_x != $this->lMargin + $this->blk[$blvl]['outer_left_margin'] || $last_w != $this->blk[$blvl]['width'] || $last_fc != $this->FillColor) {
			$x = $this->x;
			$this->Cell( ($this->blk[$blvl]['width']), $h, '', '', 0, '', 1);
			if (!$this->keep_block_together && !$this->writingHTMLheader && !$this->writingHTMLfooter) {
				$this->x = $x;
				// $state = 0 normal; 1 top; 2 bottom; 3 top and bottom
				if ($blvl == $this->blklvl) { $this->PaintDivLnBorder($state,$blvl,$h); }
				else { $this->PaintDivLnBorder(0,$blvl,$h); }
			}
		}
		$last_x = $this->lMargin + $this->blk[$blvl]['outer_left_margin'];
		$last_w = $this->blk[$blvl]['width'];
		$last_fc = $this->FillColor;
	}
	// Reset current block fill
	if (isset($this->blk[$this->blklvl]['bgcolorarray'])) {
		$bcor = $this->blk[$this->blklvl]['bgcolorarray'];
		$this->SetFColor($bcor);
	}
	$this->x = $bak_x;
	$this->divheight = $bak_h;
  }
  if ($move_y) { $this->y += $h; }
}


function SetX($x)
{
	//Set x position
	if($x >= 0)	$this->x=$x;
	else $this->x = $this->w + $x;
}

function SetY($y)
{
	//Set y position and reset x
	$this->x=$this->lMargin;
	if($y>=0)
		$this->y=$y;
	else
		$this->y=$this->h+$y;
}

function SetXY($x,$y)
{
	//Set x and y positions
	$this->SetY($y);
	$this->SetX($x);
}


function Output($name='',$dest='')
{

	global $gfpdfe_data;
	//Output PDF to some destination
	if ($this->showStats) {
		file_put_contents($gfpdfe_data->template_save_location . 'mPDF_performance_log.txt', date('Y-m-d h:i:s') . ' Generated in '.sprintf('%.2F',(microtime(true) - $this->time0))." seconds\r\n", FILE_APPEND);
	}
	
	//Finish document if necessary
	if($this->state < 3) $this->Close();
	// fn. error_get_last is only in PHP>=5.2
	if ($this->debug && function_exists('error_get_last') && error_get_last()) {
	   $e = error_get_last();
	   if (($e['type'] < 2048 && $e['type'] != 8) || (intval($e['type']) & intval(ini_get("error_reporting")))) {
		echo "<p>Error message detected - PDF file generation aborted.</p>";
		echo $e['message'].'<br />';
		echo 'File: '.$e['file'].'<br />';
		echo 'Line: '.$e['line'].'<br />';
		exit;
	   }
	}


	if (($this->PDFA || $this->PDFX) && $this->encrypted) { $this->Error("PDFA1-b or PDFX/1-a does not permit encryption of documents."); }
	if (count($this->PDFAXwarnings) && (($this->PDFA && !$this->PDFAauto) || ($this->PDFX && !$this->PDFXauto))) {
		if ($this->PDFA) {
			echo '<div>WARNING - This file could not be generated as it stands as a PDFA1-b compliant file.</div>';
			echo '<div>Action that mPDF will take to automatically force PDFA1-b compliance are shown in brackets.</div>';
		}
		else {
			echo '<div>WARNING - This file could not be generated as it stands as a PDFX/1-a compliant file.</div>';
			echo '<div>Action that mPDF will take to automatically force PDFX/1-a compliance are shown in brackets.</div>';
		}
		echo '<div>Warning(s) generated:</div><ul>';
		$this->PDFAXwarnings = array_unique($this->PDFAXwarnings);
		foreach($this->PDFAXwarnings AS $w) {
			echo '<li>'.$w.'</li>';
		}
		echo '</ul>';
		exit;
	}

	if ($this->showStats) {
			/* Blue Liquid Designs Edit
			   Log to File instead */
			$log = date('Y-m-d h:i:s') . ' Compiled in '.sprintf('%.2F',(microtime(true) - $this->time0))." seconds (total)\n\r";
			$log .= 'Peak Memory usage '.number_format((memory_get_peak_usage(true)/(1024*1024)),2)." MB\n";
			$log .= 'PDF file size '.number_format((strlen($this->buffer)/1024))." kB\n";
			$log .= 'Number of fonts '.count($this->fonts)."\n";
			file_put_contents($gfpdfe_data->template_save_location . 'mPDF_performance_log.txt', $log, FILE_APPEND);
	}


	if(is_bool($dest)) $dest=$dest ? 'D' : 'F';
	$dest=strtoupper($dest);
	if($dest=='') {
		if($name=='') {
			$name='mpdf.pdf';
			$dest='I';
		}
		else { $dest='F'; }
	}


		switch($dest) {
		   case 'I':
			if ($this->debug && !$this->allow_output_buffering && ob_get_contents()) { echo "<p>Output has already been sent from the script - PDF file generation aborted.</p>"; exit; }
			//Send to standard output
			if(PHP_SAPI!='cli') {
				//We send to a browser
				header('Content-Type: application/pdf');
				if(headers_sent())
					$this->Error('Some data has already been output to browser, can\'t send PDF file');
				if (!isset($_SERVER['HTTP_ACCEPT_ENCODING']) OR empty($_SERVER['HTTP_ACCEPT_ENCODING'])) {
					// don't use length if server using compression
					header('Content-Length: '.strlen($this->buffer));
				}
				header('Content-disposition: inline; filename="'.$name.'"');
				header('Cache-Control: public, must-revalidate, max-age=0');
				header('Pragma: public');
				header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
				header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
			}
			echo $this->buffer;
			break;
		   case 'D':
			//Download file
			header('Content-Description: File Transfer');
			if (headers_sent())
				$this->Error('Some data has already been output to browser, can\'t send PDF file');
			header('Content-Transfer-Encoding: binary');
			header('Cache-Control: public, must-revalidate, max-age=0');
			header('Pragma: public');
			header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
			header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
			header('Content-Type: application/force-download');
			header('Content-Type: application/octet-stream', false);
			header('Content-Type: application/download', false);
			header('Content-Type: application/pdf', false);
			if (!isset($_SERVER['HTTP_ACCEPT_ENCODING']) OR empty($_SERVER['HTTP_ACCEPT_ENCODING'])) {
				// don't use length if server using compression
				header('Content-Length: '.strlen($this->buffer));
			}
			header('Content-disposition: attachment; filename="'.$name.'"');
 			echo $this->buffer;
			break;
		   case 'F':
			//Save to local file
			$f=fopen($name,'wb');
			if(!$f) $this->Error('Unable to create output file: '.$name);
			fwrite($f,$this->buffer,strlen($this->buffer));
			fclose($f);
			break;
		   case 'S':
			//Return as a string
			return $this->buffer;
		   default:
			$this->Error('Incorrect output destination: '.$dest);
		}

	//======================================================================================================
	// DELETE OLD TMP FILES - Housekeeping
	// Delete any files in tmp/ directory that are >1 hrs old
		$interval = 3600;
		if ($handle = opendir(preg_replace('/\/$/','',_MPDF_TEMP_PATH))) {
		   while (false !== ($file = readdir($handle))) {
			if (!is_dir($file) && ((filemtime(_MPDF_TEMP_PATH.$file)+$interval) < time()) && ($file != "..") && ($file != ".")) { // mPDF 5.4.19
				unlink(_MPDF_TEMP_PATH.$file);
			}
		   }
		   closedir($handle);
		}
	//==============================================================================================================

	return '';
}


// *****************************************************************************
//                                                                             *
//                             Protected methods                               *
//                                                                             *
// *****************************************************************************
function _dochecks()
{
	//Check for locale-related bug
	if(1.1==1)
		$this->Error('Don\'t alter the locale before including mPDF');
	//Check for decimal separator
	if(sprintf('%.1f',1.0)!='1.0')
		setlocale(LC_NUMERIC,'C');
	// mPDF 5.4.11
	$mqr=ini_get("magic_quotes_runtime");
	if ($mqr) { $this->Error('mPDF requires magic_quotes_runtime to be turned off e.g. by using ini_set("magic_quotes_runtime", 0);'); }
}

function _begindoc()
{
	//Start document
	$this->state=1;
	$this->_out('%PDF-'.$this->pdf_version);
	$this->_out('%'.chr(226).chr(227).chr(207).chr(211));	// 4 chars > 128 to show binary file
}




function _putpages()
{
	$nb=$this->page;
	$filter=($this->compress) ? '/Filter /FlateDecode ' : '';

	if($this->DefOrientation=='P') {
		$defwPt=$this->fwPt;
		$defhPt=$this->fhPt;
	}
	else {
		$defwPt=$this->fhPt;
		$defhPt=$this->fwPt;
	}
	$annotid=(3+2*$nb);

	// Active Forms
	$totaladdnum = 0;
	for($n=1;$n<=$nb;$n++) {
		if (isset($this->PageLinks[$n])) { $totaladdnum += count($this->PageLinks[$n]); }

	}

	// Select unused fonts (usually default font)
	$unused = array();
	foreach($this->fonts as $fk=>$font) {
	   if (!$font['used'] && ($font['type']=='TTF')) {
		$unused[] = $fk;
	   }
	}


	for($n=1;$n<=$nb;$n++)
	{
		$thispage = $this->pages[$n];
		unset($this->pages[$n]);
		if(isset($this->OrientationChanges[$n])) {
			$hPt=$this->pageDim[$n]['w']*_MPDFK;
			$wPt=$this->pageDim[$n]['h']*_MPDFK;
			$owidthPt_LR = $this->pageDim[$n]['outer_width_TB']*_MPDFK;
			$owidthPt_TB = $this->pageDim[$n]['outer_width_LR']*_MPDFK;
		}
		else {
			$wPt=$this->pageDim[$n]['w']*_MPDFK;
			$hPt=$this->pageDim[$n]['h']*_MPDFK;
			$owidthPt_LR = $this->pageDim[$n]['outer_width_LR']*_MPDFK;
			$owidthPt_TB = $this->pageDim[$n]['outer_width_TB']*_MPDFK;
		}
		// Remove references to unused fonts (usually default font)
		foreach($unused as $fk) {
			if ($this->fonts[$fk]['sip'] || $this->fonts[$fk]['smp']) {
				foreach($this->fonts[$fk]['subsetfontids'] AS $k => $fid) {
						$thispage = preg_replace('/\s\/F'.$fid.' \d[\d.]* Tf\s/is',' ',$thispage);
				}
			}
			else {
				$thispage = preg_replace('/\s\/F'.$this->fonts[$fk]['i'].' \d[\d.]* Tf\s/is',' ',$thispage);
			}
		}
		//Replace number of pages
		if(!empty($this->aliasNbPg)) {
			if (!$this->onlyCoreFonts) { $s1 = $this->UTF8ToUTF16BE($this->aliasNbPg, false); }
			$s2 = $this->aliasNbPg;
			if (!$this->onlyCoreFonts) { $r1 = $this->UTF8ToUTF16BE($nb, false); }
			$r2 = $nb;
			if (preg_match_all('/{mpdfheadernbpg (C|R) ff=(\S*) fs=(\S*) fz=(.*?)}/',$thispage,$m)) {
				for($hi=0;$hi<count($m[0]);$hi++) {
					$pos = $m[1][$hi];
					$hff = $m[2][$hi];
					$hfst = $m[3][$hi];
					$hfsz = $m[4][$hi];
					$this->SetFont($hff,$hfst,$hfsz, false);
					$x1 = $this->GetStringWidth($this->aliasNbPg);
					$x2 = $this->GetStringWidth($nb);
					$xadj = $x1 - $x2;
					if ($pos=='C') { $xadj /= 2; }
					$rep = sprintf(' q 1 0 0 1 %.3F 0 cm ', $xadj*_MPDFK);
					$thispage = str_replace($m[0][$hi], $rep, $thispage);
				}
			}
			if (!$this->onlyCoreFonts) { $thispage=str_replace($s1,$r1,$thispage); }
			$thispage=str_replace($s2,$r2,$thispage);

			// And now for any SMP/SIP fonts subset using <HH> format
			$r = '';
			$nstr = "$nb";
			for($i=0;$i<strlen($nstr);$i++) {
				$r .= sprintf("%02s", strtoupper(dechex(intval($nstr[$i])+48)));
			}
			$thispage=str_replace($this->aliasNbPgHex,$r,$thispage);

		}
		//Replace number of pages in group
		if(!empty($this->aliasNbPgGp)) {
			if (!$this->onlyCoreFonts) { $s1 = $this->UTF8ToUTF16BE($this->aliasNbPgGp, false); }
			$s2 = $this->aliasNbPgGp;
			$nbt = $this->docPageNumTotal($n);
			if (!$this->onlyCoreFonts) { $r1 = $this->UTF8ToUTF16BE($nbt, false); }
			$r2 = $nbt;
			if (preg_match_all('/{mpdfheadernbpggp (C|R) ff=(\S*) fs=(\S*) fz=(.*?)}/',$thispage,$m)) {
				for($hi=0;$hi<count($m[0]);$hi++) {
					$pos = $m[1][$hi];
					$hff = $m[2][$hi];
					$hfst = $m[3][$hi];
					$hfsz = $m[4][$hi];
					$this->SetFont($hff,$hfst,$hfsz, false);
					$x1 = $this->GetStringWidth($this->aliasNbPgGp);
					$x2 = $this->GetStringWidth($nbt);
					$xadj = $x1 - $x2;
					if ($pos=='C') { $xadj /= 2; }
					$rep = sprintf(' q 1 0 0 1 %.3F 0 cm ', $xadj*_MPDFK);
					$thispage = str_replace($m[0][$hi], $rep, $thispage);
				}
			}
			if (!$this->onlyCoreFonts) { $thispage=str_replace($s1,$r1,$thispage); }
			$thispage=str_replace($s2,$r2,$thispage);

			// And now for any SMP/SIP fonts subset using <HH> format
			$r = '';
			$nstr = "$nbt";
			for($i=0;$i<strlen($nstr);$i++) {
				$r .= sprintf("%02s", strtoupper(dechex(intval($nstr[$i])+48)));
			}
			$thispage=str_replace($this->aliasNbPgGpHex,$r,$thispage);

		}
		$thispage = preg_replace('/(\s*___BACKGROUND___PATTERNS'.date('jY').'\s*)/', " ", $thispage);
		$thispage = preg_replace('/(\s*___HEADER___MARKER'.date('jY').'\s*)/', " ", $thispage);
		$thispage = preg_replace('/(\s*___PAGE___START'.date('jY').'\s*)/', " ", $thispage);
		$thispage = preg_replace('/(\s*___TABLE___BACKGROUNDS'.date('jY').'\s*)/', " ", $thispage);

		//Page
		$this->_newobj();
		$this->_out('<</Type /Page');
		$this->_out('/Parent 1 0 R');
		if(isset($this->OrientationChanges[$n])) {
			$this->_out(sprintf('/MediaBox [0 0 %.3F %.3F]',$hPt,$wPt));
			//If BleedBox is defined, it must be larger than the TrimBox, but smaller than the MediaBox
			$bleedMargin = $this->pageDim[$n]['bleedMargin']*_MPDFK;
			if ($bleedMargin && ($owidthPt_TB || $owidthPt_LR)) {
				$x0 = $owidthPt_TB-$bleedMargin;
				$y0 = $owidthPt_LR-$bleedMargin;
				$x1 = $hPt-$owidthPt_TB+$bleedMargin;
				$y1 = $wPt-$owidthPt_LR+$bleedMargin;
				$this->_out(sprintf('/BleedBox [%.3F %.3F %.3F %.3F]', $x0, $y0, $x1, $y1));
			}
			$this->_out(sprintf('/TrimBox [%.3F %.3F %.3F %.3F]', $owidthPt_TB, $owidthPt_LR, ($hPt-$owidthPt_TB), ($wPt-$owidthPt_LR)));
			if (isset($this->OrientationChanges[$n]) && $this->displayDefaultOrientation) {
				if ($this->DefOrientation=='P') { $this->_out('/Rotate 270'); }
				else { $this->_out('/Rotate 90'); }
			}
		}
		//else if($wPt != $defwPt || $hPt != $defhPt) {
		else {
			$this->_out(sprintf('/MediaBox [0 0 %.3F %.3F]',$wPt,$hPt));
			$bleedMargin = $this->pageDim[$n]['bleedMargin']*_MPDFK;
			if ($bleedMargin && ($owidthPt_TB || $owidthPt_LR)) {
				$x0 = $owidthPt_LR-$bleedMargin;
				$y0 = $owidthPt_TB-$bleedMargin;
				$x1 = $wPt-$owidthPt_LR+$bleedMargin;
				$y1 = $hPt-$owidthPt_TB+$bleedMargin;
				$this->_out(sprintf('/BleedBox [%.3F %.3F %.3F %.3F]', $x0, $y0, $x1, $y1));
			}
			$this->_out(sprintf('/TrimBox [%.3F %.3F %.3F %.3F]', $owidthPt_LR, $owidthPt_TB, ($wPt-$owidthPt_LR), ($hPt-$owidthPt_TB)));
		}
		$this->_out('/Resources 2 0 R');

		// Important to keep in RGB colorSpace when using transparency
		if (!$this->PDFA && !$this->PDFX) {
			if ($this->restrictColorSpace == 3)
				$this->_out('/Group << /Type /Group /S /Transparency /CS /DeviceCMYK >> ');
			else if ($this->restrictColorSpace == 1)
				$this->_out('/Group << /Type /Group /S /Transparency /CS /DeviceGray >> ');
			else
				$this->_out('/Group << /Type /Group /S /Transparency /CS /DeviceRGB >> ');
		}

		$annotsnum = 0;
		if (isset($this->PageLinks[$n])) { $annotsnum += count($this->PageLinks[$n]); }

		if ($annotsnum || $formsnum) {
			$s = '/Annots [ ';
			for($i=0;$i<$annotsnum;$i++) {
				$s .= ($annotid + $i) . ' 0 R ';
			}
			$annotid += $annotsnum;
			$s .= '] ';
			$this->_out($s);
		}

		$this->_out('/Contents '.($this->n+1).' 0 R>>');
		$this->_out('endobj');

		//Page content
		$this->_newobj();
		$p=($this->compress) ? gzcompress($thispage) : $thispage;
		$this->_out('<<'.$filter.'/Length '.strlen($p).'>>');
		$this->_putstream($p);
		$this->_out('endobj');
	}
	$this->_putannots($n);

	//Pages root
	$this->offsets[1]=strlen($this->buffer);
	$this->_out('1 0 obj');
	$this->_out('<</Type /Pages');
	$kids='/Kids [';
	for($i=0;$i<$nb;$i++)
		$kids.=(3+2*$i).' 0 R ';
	$this->_out($kids.']');
	$this->_out('/Count '.$nb);
	$this->_out(sprintf('/MediaBox [0 0 %.3F %.3F]',$defwPt,$defhPt));
	$this->_out('>>');
	$this->_out('endobj');
}


function _putannots($n) {
	$filter=($this->compress) ? '/Filter /FlateDecode ' : '';
	$nb=$this->page;
	for($n=1;$n<=$nb;$n++)
	{
		$annotobjs = array();
		if(isset($this->PageLinks[$n]) || isset($this->PageAnnots[$n]) || count($this->form->forms) > 0 ) {
			$wPt=$this->pageDim[$n]['w']*_MPDFK;
			$hPt=$this->pageDim[$n]['h']*_MPDFK;

			//Links
			if(isset($this->PageLinks[$n])) {
			   foreach($this->PageLinks[$n] as $key => $pl) {
				$this->_newobj();
				$annot='';
				$rect=sprintf('%.3F %.3F %.3F %.3F',$pl[0],$pl[1],$pl[0]+$pl[2],$pl[1]-$pl[3]);
				$annot .= '<</Type /Annot /Subtype /Link /Rect ['.$rect.']';
				$annot .= ' /Contents '.$this->_UTF16BEtextstring($pl[4]);
				$annot .= ' /NM '.$this->_textstring(sprintf('%04u-%04u', $n, $key));
				$annot .= ' /M '.$this->_textstring('D:'.date('YmdHis'));
				$annot .= ' /Border [0 0 0]';
				// Use this (instead of /Border) to specify border around link
		//		$annot .= ' /BS <</W 1';	// Width on points; 0 = no line
		//		$annot .= ' /S /D';		// style - [S]olid, [D]ashed, [B]eveled, [I]nset, [U]nderline
		//		$annot .= ' /D [3 2]';		// Dash array - if dashed
		//		$annot .= ' >>';
		//		$annot .= ' /C [1 0 0]';	// Color RGB

				if ($this->PDFA || $this->PDFX) { $annot .= ' /F 28'; }
				if (strpos($pl[4],'@')===0) {
					$p=substr($pl[4],1);
					//	$h=isset($this->OrientationChanges[$p]) ? $wPt : $hPt;
					$htarg=$this->pageDim[$p]['h']*_MPDFK;
					$annot.=sprintf(' /Dest [%d 0 R /XYZ 0 %.3F null]>>',1+2*$p,$htarg);
				}
				else if(is_string($pl[4])) {
					$annot .= ' /A <</S /URI /URI '.$this->_textstring($pl[4]).'>> >>';
				}
				else {
					$l=$this->links[$pl[4]];
					// may not be set if #link points to non-existent target
					if (isset($this->pageDim[$l[0]]['h'])) { $htarg=$this->pageDim[$l[0]]['h']*_MPDFK; }
					else { $htarg=$this->h*_MPDFK; } // doesn't really matter
					$annot.=sprintf(' /Dest [%d 0 R /XYZ 0 %.3F null]>>',1+2*$l[0],$htarg-$l[1]*_MPDFK);
				}
				$this->_out($annot);
				$this->_out('endobj');
			   }
			}



		}
	}
}




function _putfonts() {
	$nf=$this->n;
	foreach($this->FontFiles as $fontkey=>$info) {
	   // TrueType embedded
	   if (isset($info['type']) && $info['type']=='TTF' && !$info['sip'] && !$info['smp']) {
		$used = true;
		$asSubset = false;
		foreach($this->fonts AS $k=>$f) {
			if ($f['fontkey'] == $fontkey && $f['type']=='TTF') {
				$used = $f['used'];
				if ($used) {
					$nChars = (ord($f['cw'][0]) << 8) + ord($f['cw'][1]);
					$usage = intval(count($f['subset'])*100 / $nChars);
					$fsize = $info['length1'];
					// Always subset the very large TTF files
					if ($fsize > ($this->maxTTFFilesize *1024)) { $asSubset = true; }
					else if ($usage < $this->percentSubset) { $asSubset = true; }
				}
				if ($f['unAGlyphs']) $aaSubset = true;	// mPDF 5.4.05
				if ($this->PDFA || $this->PDFX)  $asSubset = false;
				$this->fonts[$k]['asSubset'] = $asSubset;
				break;
			}
		}
		if ($used && !$asSubset) {
			//Font file embedding
			$this->_newobj();
			$this->FontFiles[$fontkey]['n']=$this->n;
			$font='';
			$originalsize = $info['length1'];
			if ($this->repackageTTF || $this->fonts[$fontkey]['TTCfontID']>0) {
				// First see if there is a cached compressed file
				if (file_exists(_MPDF_TTFONTDATAPATH.$fontkey.'.ps.z')) {
					$f=fopen(_MPDF_TTFONTDATAPATH.$fontkey.'.ps.z','rb');
					if(!$f) { $this->Error('Font file .ps.z not found'); }
					while(!feof($f)) { $font .= fread($f, 2048); }
					fclose($f);
					include(_MPDF_TTFONTDATAPATH.$fontkey.'.ps.php');	// sets $originalsize (of repackaged font)
				}
				else {
					if (!class_exists('TTFontFile', false)) { include(_MPDF_PATH .'classes/ttfontsuni.php'); }
					$ttf = new TTFontFile();
					$font = $ttf->repackageTTF($this->FontFiles[$fontkey]['ttffile'], $this->fonts[$fontkey]['TTCfontID'], $this->debugfonts, $this->fonts[$fontkey]['unAGlyphs']);	// mPDF 5.4.05

					$originalsize = strlen($font);
					$font = gzcompress($font);
					unset($ttf);
					if (is_writable(dirname(_MPDF_TTFONTDATAPATH.'x'))) {
						$fh = fopen(_MPDF_TTFONTDATAPATH.$fontkey.'.ps.z',"wb");
						fwrite($fh,$font,strlen($font));
						fclose($fh);
						$fh = fopen(_MPDF_TTFONTDATAPATH.$fontkey.'.ps.php',"wb");
						$len = "<?php \n";
						$len.='$originalsize='.$originalsize.";\n";
						$len.="?>";
						fwrite($fh,$len,strlen($len));
						fclose($fh);
					}
				}
			}
			else {
				// First see if there is a cached compressed file
				if (file_exists(_MPDF_TTFONTDATAPATH.$fontkey.'.z')) {
					$f=fopen(_MPDF_TTFONTDATAPATH.$fontkey.'.z','rb');
					if(!$f) { $this->Error('Font file not found'); }
					while(!feof($f)) { $font .= fread($f, 2048); }
					fclose($f);
				}
				else {
					$f=fopen($this->FontFiles[$fontkey]['ttffile'],'rb');
					if(!$f) { $this->Error('Font file not found'); }
					while(!feof($f)) { $font .= fread($f, 2048); }
					fclose($f);
					$font = gzcompress($font);
					if (is_writable(dirname(_MPDF_TTFONTDATAPATH.'x'))) {
						$fh = fopen(_MPDF_TTFONTDATAPATH.$fontkey.'.z',"wb");
						fwrite($fh,$font,strlen($font));
						fclose($fh);
					}
				}
			}

			$this->_out('<</Length '.strlen($font));
			$this->_out('/Filter /FlateDecode');
			$this->_out('/Length1 '.$originalsize);
			$this->_out('>>');
			$this->_putstream($font);
			$this->_out('endobj');
		}
	   }
	}

	$nfonts = count($this->fonts);
	$fctr = 1;
	foreach($this->fonts as $k=>$font) {
		//Font objects
		$type=$font['type'];
		$name=$font['name'];
		if ((!isset($font['used']) || !$font['used']) && $type=='TTF') { continue; }
		if (isset($font['asSubset'])) { $asSubset = $font['asSubset']; }
		else { $asSubset = ''; }
		if($type=='core') {
			//Standard font
			$this->fonts[$k]['n']=$this->n+1;
			if ($this->PDFA || $this->PDFX) { $this->Error('Core fonts are not allowed in PDF/A1-b or PDFX/1-a files (Times, Helvetica, Courier etc.)'); }
			$this->_newobj();
			$this->_out('<</Type /Font');
			$this->_out('/BaseFont /'.$name);
			$this->_out('/Subtype /Type1');
			if($name!='Symbol' && $name!='ZapfDingbats') {
				$this->_out('/Encoding /WinAnsiEncoding');
			}
			$this->_out('>>');
			$this->_out('endobj');
		}
		// TrueType embedded SUBSETS for SIP (CJK extB containing Supplementary Ideographic Plane 2)
		// Or Unicode Plane 1 - Supplementary Multilingual Plane
		else if ($type=='TTF' && ($font['sip'] || $font['smp'])) {
		   if (!$font['used']) { continue; }
		   $ssfaid="AA";
		   if (!class_exists('TTFontFile', false)) { include(_MPDF_PATH .'classes/ttfontsuni.php'); }
		   $ttf = new TTFontFile();
		   for($sfid=0;$sfid<count($font['subsetfontids']);$sfid++) {
			$this->fonts[$k]['n'][$sfid]=$this->n+1;		// NB an array for subset
			$subsetname = 'MPDF'.$ssfaid.'+'.$font['name'];
			$ssfaid++;
			$subset = $font['subsets'][$sfid];
			unset($subset[0]);
			$ttfontstream = $ttf->makeSubsetSIP($font['ttffile'], $subset, $font['TTCfontID'], $this->debugfonts);
			$ttfontsize = strlen($ttfontstream);
			$fontstream = gzcompress($ttfontstream);
			$widthstring = '';
			$toUnistring = '';
			foreach($font['subsets'][$sfid] AS $cp=>$u) {
				$w = $this->_getCharWidth($font['cw'], $u);
				if ($w !== false) {
					$widthstring .= $w.' ';
				}
				else {
					$widthstring .= round($ttf->defaultWidth).' ';
				}
				if ($u > 65535) {
					$utf8 = chr(($u>>18)+240).chr((($u>>12)&63)+128).chr((($u>>6)&63)+128) .chr(($u&63)+128);
					$utf16 = mb_convert_encoding($utf8, 'UTF-16BE', 'UTF-8');
					$l1 = ord($utf16[0]);
					$h1 = ord($utf16[1]);
					$l2 = ord($utf16[2]);
					$h2 = ord($utf16[3]);
					$toUnistring .= sprintf("<%02s> <%02s%02s%02s%02s>\n", strtoupper(dechex($cp)), strtoupper(dechex($l1)), strtoupper(dechex($h1)), strtoupper(dechex($l2)), strtoupper(dechex($h2)));
				}
				else {
					$toUnistring .= sprintf("<%02s> <%04s>\n", strtoupper(dechex($cp)), strtoupper(dechex($u)));
				}
			}

			//Additional Type1 or TrueType font
			$this->_newobj();
			$this->_out('<</Type /Font');
			$this->_out('/BaseFont /'.$subsetname);
			$this->_out('/Subtype /TrueType');
			$this->_out('/FirstChar 0 /LastChar '.(count($font['subsets'][$sfid])-1));
			$this->_out('/Widths '.($this->n+1).' 0 R');
			$this->_out('/FontDescriptor '.($this->n+2).' 0 R');
			$this->_out('/ToUnicode '.($this->n + 3).' 0 R');
			$this->_out('>>');
			$this->_out('endobj');

			//Widths
			$this->_newobj();
			$this->_out('['.$widthstring.']');
			$this->_out('endobj');

			//Descriptor
			$this->_newobj();
			$s='<</Type /FontDescriptor /FontName /'.$subsetname."\n";
			foreach($font['desc'] as $kd=>$v) {
				if ($kd == 'Flags') { $v = $v | 4; $v = $v & ~32; }	// SYMBOLIC font flag
				$s.=' /'.$kd.' '.$v."\n";
			}
			$s.='/FontFile2 '.($this->n + 2).' 0 R';
			$this->_out($s.'>>');
			$this->_out('endobj');

			// ToUnicode
			$this->_newobj();
			$toUni = "/CIDInit /ProcSet findresource begin\n";
			$toUni .= "12 dict begin\n";
			$toUni .= "begincmap\n";
			$toUni .= "/CIDSystemInfo\n";
			$toUni .= "<</Registry (Adobe)\n";
			$toUni .= "/Ordering (UCS)\n";
			$toUni .= "/Supplement 0\n";
			$toUni .= ">> def\n";
			$toUni .= "/CMapName /Adobe-Identity-UCS def\n";
			$toUni .= "/CMapType 2 def\n";
			$toUni .= "1 begincodespacerange\n";
			$toUni .= "<00> <FF>\n";
			$toUni .= "endcodespacerange\n";
			$toUni .= count($font['subsets'][$sfid])." beginbfchar\n";
			$toUni .= $toUnistring;
			$toUni .= "endbfchar\n";
			$toUni .= "endcmap\n";
			$toUni .= "CMapName currentdict /CMap defineresource pop\n";
			$toUni .= "end\n";
			$toUni .= "end\n";

			$this->_out('<</Length '.(strlen($toUni)).'>>');
			$this->_putstream($toUni);
			$this->_out('endobj');

			//Font file
			$this->_newobj();
			$this->_out('<</Length '.strlen($fontstream));
			$this->_out('/Filter /FlateDecode');
			$this->_out('/Length1 '.$ttfontsize);
			$this->_out('>>');
			$this->_putstream($fontstream);
			$this->_out('endobj');
		   }	// foreach subset
		   unset($ttf);
		}
		// TrueType embedded SUBSETS or FULL
		else if ($type=='TTF') {
			$this->fonts[$k]['n']=$this->n+1;
			if ($asSubset ) {
				$ssfaid="A";
				if (!class_exists('TTFontFile', false)) { include(_MPDF_PATH .'classes/ttfontsuni.php'); }
				$ttf = new TTFontFile();
				$fontname = 'MPDFA'.$ssfaid.'+'.$font['name'];
				$subset = $font['subset'];
				unset($subset[0]);
				$ttfontstream = $ttf->makeSubset($font['ttffile'], $subset, $font['TTCfontID'], $this->debugfonts, $font['unAGlyphs']);	// mPDF 5.4.05
				$ttfontsize = strlen($ttfontstream);
				$fontstream = gzcompress($ttfontstream);
				$codeToGlyph = $ttf->codeToGlyph;
				unset($codeToGlyph[0]);
			}
			else { $fontname = $font['name']; }
			// Type0 Font
			// A composite font - a font composed of other fonts, organized hierarchically
			$this->_newobj();
			$this->_out('<</Type /Font');
			$this->_out('/Subtype /Type0');
			$this->_out('/BaseFont /'.$fontname.'');
			$this->_out('/Encoding /Identity-H');
			$this->_out('/DescendantFonts ['.($this->n + 1).' 0 R]');
			$this->_out('/ToUnicode '.($this->n + 2).' 0 R');
			$this->_out('>>');
			$this->_out('endobj');

			// CIDFontType2
			// A CIDFont whose glyph descriptions are based on TrueType font technology
			$this->_newobj();
			$this->_out('<</Type /Font');
			$this->_out('/Subtype /CIDFontType2');
			$this->_out('/BaseFont /'.$fontname.'');
			$this->_out('/CIDSystemInfo '.($this->n + 2).' 0 R');
			$this->_out('/FontDescriptor '.($this->n + 3).' 0 R');
			if (isset($font['desc']['MissingWidth'])){
				$this->_out('/DW '.$font['desc']['MissingWidth'].'');
			}

			if (!$asSubset && file_exists(_MPDF_TTFONTDATAPATH.$font['fontkey'].'.cw')) {
					$w = '';
					$w=file_get_contents(_MPDF_TTFONTDATAPATH.$font['fontkey'].'.cw');
					$this->_out($w);
			}
			else {
				$this->_putTTfontwidths($font, $asSubset, $ttf->maxUni);
			}

			$this->_out('/CIDToGIDMap '.($this->n + 4).' 0 R');
			$this->_out('>>');
			$this->_out('endobj');

			// ToUnicode
			$this->_newobj();
			$toUni = "/CIDInit /ProcSet findresource begin\n";
			$toUni .= "12 dict begin\n";
			$toUni .= "begincmap\n";
			$toUni .= "/CIDSystemInfo\n";
			$toUni .= "<</Registry (Adobe)\n";
			$toUni .= "/Ordering (UCS)\n";
			$toUni .= "/Supplement 0\n";
			$toUni .= ">> def\n";
			$toUni .= "/CMapName /Adobe-Identity-UCS def\n";
			$toUni .= "/CMapType 2 def\n";
			$toUni .= "1 begincodespacerange\n";
			$toUni .= "<0000> <FFFF>\n";
			$toUni .= "endcodespacerange\n";
			$toUni .= "1 beginbfrange\n";
			$toUni .= "<0000> <FFFF> <0000>\n";
			$toUni .= "endbfrange\n";
			$toUni .= "endcmap\n";
			$toUni .= "CMapName currentdict /CMap defineresource pop\n";
			$toUni .= "end\n";
			$toUni .= "end\n";
			$this->_out('<</Length '.(strlen($toUni)).'>>');
			$this->_putstream($toUni);
			$this->_out('endobj');


			// CIDSystemInfo dictionary
			$this->_newobj();
			$this->_out('<</Registry (Adobe)');
			$this->_out('/Ordering (UCS)');
			$this->_out('/Supplement 0');
			$this->_out('>>');
			$this->_out('endobj');

			// Font descriptor
			$this->_newobj();
			$this->_out('<</Type /FontDescriptor');
			$this->_out('/FontName /'.$fontname);
			foreach($font['desc'] as $kd=>$v) {
				if ($asSubset && $kd == 'Flags') { $v = $v | 4; $v = $v & ~32; }	// SYMBOLIC font flag
				$this->_out(' /'.$kd.' '.$v);
			}
			if ($font['panose']) {
				$this->_out(' /Style << /Panose <'.$font['panose'].'> >>');
			}
			if ($asSubset ) {
				$this->_out('/FontFile2 '.($this->n + 2).' 0 R');
			}
			else if ($font['fontkey']) {
				// obj ID of a stream containing a TrueType font program
				$this->_out('/FontFile2 '.$this->FontFiles[$font['fontkey']]['n'].' 0 R');
			}
			$this->_out('>>');
			$this->_out('endobj');

			// Embed CIDToGIDMap
			// A specification of the mapping from CIDs to glyph indices
			if ($asSubset ) {
				$cidtogidmap = '';
				$cidtogidmap = str_pad('', 256*256*2, "\x00");
				foreach($codeToGlyph as $cc=>$glyph) {
					$cidtogidmap[$cc*2] = chr($glyph >> 8);
					$cidtogidmap[$cc*2 + 1] = chr($glyph & 0xFF);
				}
				$cidtogidmap = gzcompress($cidtogidmap);
			}
			else {
				// First see if there is a cached CIDToGIDMapfile
				$cidtogidmap = '';
				if (file_exists(_MPDF_TTFONTDATAPATH.$font['fontkey'].'.cgm')) {
					$f=fopen(_MPDF_TTFONTDATAPATH.$font['fontkey'].'.cgm','rb');
					while(!feof($f)) { $cidtogidmap .= fread($f, 2048); }
					fclose($f);
				}
				else {
					if (!class_exists('TTFontFile', false)) { include(_MPDF_PATH .'classes/ttfontsuni.php'); }
					$ttf = new TTFontFile();
					$charToGlyph = $ttf->getCTG($font['ttffile'], $font['TTCfontID'], $this->debugfonts, $font['unAGlyphs']);	// mPDF 5.4.05
					$cidtogidmap = str_pad('', 256*256*2, "\x00");
					foreach($charToGlyph as $cc=>$glyph) {
						$cidtogidmap[$cc*2] = chr($glyph >> 8);
						$cidtogidmap[$cc*2 + 1] = chr($glyph & 0xFF);
					}
					unset($ttf);
					$cidtogidmap = gzcompress($cidtogidmap);
					if (is_writable(dirname(_MPDF_TTFONTDATAPATH.'x'))) {
						$fh = fopen(_MPDF_TTFONTDATAPATH.$font['fontkey'].'.cgm',"wb");
						fwrite($fh,$cidtogidmap,strlen($cidtogidmap));
						fclose($fh);
					}
				}
			}
			$this->_newobj();
			$this->_out('<</Length '.strlen($cidtogidmap).'');
			$this->_out('/Filter /FlateDecode');
			$this->_out('>>');
			$this->_putstream($cidtogidmap);
			$this->_out('endobj');

			//Font file
			if ($asSubset ) {
				$this->_newobj();
				$this->_out('<</Length '.strlen($fontstream));
				$this->_out('/Filter /FlateDecode');
				$this->_out('/Length1 '.$ttfontsize);
				$this->_out('>>');
				$this->_putstream($fontstream);
				$this->_out('endobj');
				unset($ttf);
			}
		}
		else { $this->Error('Unsupported font type: '.$type.' ('.$name.')'); }
	}
}



function _putTTfontwidths(&$font, $asSubset, $maxUni) {
	if ($asSubset && file_exists(_MPDF_TTFONTDATAPATH.$font['fontkey'].'.cw127.php')) {
		include(_MPDF_TTFONTDATAPATH.$font['fontkey'].'.cw127.php') ;
		$startcid = 128;
	}
	else {
		$rangeid = 0;
		$range = array();
		$prevcid = -2;
		$prevwidth = -1;
		$interval = false;
		$startcid = 1;
	}
	if ($asSubset) { $cwlen = $maxUni + 1; }
	else { $cwlen = (strlen($font['cw'])/2); }

	// for each character
	for ($cid=$startcid; $cid<$cwlen; $cid++) {
		if ($cid==128 && $asSubset && (!file_exists(_MPDF_TTFONTDATAPATH.$font['fontkey'].'.cw127.php'))) {
			if (is_writable(dirname(_MPDF_TTFONTDATAPATH.'x'))) {
				$fh = fopen(_MPDF_TTFONTDATAPATH.$font['fontkey'].'.cw127.php',"wb");
				$cw127='<?php'."\n";
				$cw127.='$rangeid='.$rangeid.";\n";
				$cw127.='$prevcid='.$prevcid.";\n";
				$cw127.='$prevwidth='.$prevwidth.";\n";
				if ($interval) { $cw127.='$interval=true'.";\n"; }
				else { $cw127.='$interval=false'.";\n"; }
				$cw127.='$range='.var_export($range,true).";\n";
				$cw127.="?>";
				fwrite($fh,$cw127,strlen($cw127));
				fclose($fh);
			}
		}
		if ($font['cw'][$cid*2] == "\00" && $font['cw'][$cid*2+1] == "\00") { continue; }
		$width = (ord($font['cw'][$cid*2]) << 8) + ord($font['cw'][$cid*2+1]);
		if ($width == 65535) { $width = 0; }
		if ($asSubset && $cid > 255 && (!isset($font['subset'][$cid]) || !$font['subset'][$cid])) {
			continue;
		}
		if (!isset($font['dw']) || (isset($font['dw']) && $width != $font['dw'])) {
			if ($cid == ($prevcid + 1)) {
				// consecutive CID
				if ($width == $prevwidth) {
					if ($width == $range[$rangeid][0]) {
						$range[$rangeid][] = $width;
					} else {
						array_pop($range[$rangeid]);
						// new range
						$rangeid = $prevcid;
						$range[$rangeid] = array();
						$range[$rangeid][] = $prevwidth;
						$range[$rangeid][] = $width;
					}
					$interval = true;
					$range[$rangeid]['interval'] = true;
				} else {
					if ($interval) {
						// new range
						$rangeid = $cid;
						$range[$rangeid] = array();
						$range[$rangeid][] = $width;
					} else {
						$range[$rangeid][] = $width;
					}
					$interval = false;
				}
			} else {
				// new range
				$rangeid = $cid;
				$range[$rangeid] = array();
				$range[$rangeid][] = $width;
				$interval = false;
			}
			$prevcid = $cid;
			$prevwidth = $width;
		}
	}
	$w = $this->_putfontranges($range);
	$this->_out($w);
	if (!$asSubset) {
		if (is_writable(dirname(_MPDF_TTFONTDATAPATH.'x'))) {
			$fh = fopen(_MPDF_TTFONTDATAPATH.$font['fontkey'].'.cw',"wb");
			fwrite($fh,$w,strlen($w));
			fclose($fh);
		}
	}
}

function _putfontranges(&$range) {
	// optimize ranges
	$prevk = -1;
	$nextk = -1;
	$prevint = false;
	foreach ($range as $k => $ws) {
		$cws = count($ws);
		if (($k == $nextk) AND (!$prevint) AND ((!isset($ws['interval'])) OR ($cws < 4))) {
			if (isset($range[$k]['interval'])) {
				unset($range[$k]['interval']);
			}
			$range[$prevk] = array_merge($range[$prevk], $range[$k]);
			unset($range[$k]);
		} else {
			$prevk = $k;
		}
		$nextk = $k + $cws;
		if (isset($ws['interval'])) {
			if ($cws > 3) {
				$prevint = true;
			} else {
				$prevint = false;
			}
			unset($range[$k]['interval']);
			--$nextk;
		} else {
			$prevint = false;
		}
	}
	// output data
	$w = '';
	foreach ($range as $k => $ws) {
		if (count(array_count_values($ws)) == 1) {
			// interval mode is more compact
			$w .= ' '.$k.' '.($k + count($ws) - 1).' '.$ws[0];
		} else {
			// range mode
			$w .= ' '.$k.' [ '.implode(' ', $ws).' ]' . "\n";
		}
	}
	return '/W ['.$w.' ]';
}


function _putfontwidths(&$font, $cidoffset=0) {
	ksort($font['cw']);
	unset($font['cw'][65535]);
	$rangeid = 0;
	$range = array();
	$prevcid = -2;
	$prevwidth = -1;
	$interval = false;
	// for each character
	foreach ($font['cw'] as $cid => $width) {
		$cid -= $cidoffset;
		if (!isset($font['dw']) || (isset($font['dw']) && $width != $font['dw'])) {
			if ($cid == ($prevcid + 1)) {
				// consecutive CID
				if ($width == $prevwidth) {
					if ($width == $range[$rangeid][0]) {
						$range[$rangeid][] = $width;
					} else {
						array_pop($range[$rangeid]);
						// new range
						$rangeid = $prevcid;
						$range[$rangeid] = array();
						$range[$rangeid][] = $prevwidth;
						$range[$rangeid][] = $width;
					}
					$interval = true;
					$range[$rangeid]['interval'] = true;
				} else {
					if ($interval) {
						// new range
						$rangeid = $cid;
						$range[$rangeid] = array();
						$range[$rangeid][] = $width;
					} else {
						$range[$rangeid][] = $width;
					}
					$interval = false;
				}
			} else {
				// new range
				$rangeid = $cid;
				$range[$rangeid] = array();
				$range[$rangeid][] = $width;
				$interval = false;
			}
			$prevcid = $cid;
			$prevwidth = $width;
		}
	}
	$this->_out($this->_putfontranges($range));
}





function _putimages()
{
	$filter=($this->compress) ? '/Filter /FlateDecode ' : '';
	reset($this->images);
	while(list($file,$info)=each($this->images)) {
		$this->_newobj();
		$this->images[$file]['n']=$this->n;
		$this->_out('<</Type /XObject');
		$this->_out('/Subtype /Image');
		$this->_out('/Width '.$info['w']);
		$this->_out('/Height '.$info['h']);
		if (isset($info['masked'])) {
			$this->_out('/SMask '.($this->n - 1).' 0 R');
		}
		if($info['cs']=='Indexed') {
			if ($this->PDFX || ($this->PDFA && $this->restrictColorSpace==3)) { $this->Error("PDFA1-b and PDFX/1-a files do not permit using mixed colour space (".$file.")."); }
			$this->_out('/ColorSpace [/Indexed /DeviceRGB '.(strlen($info['pal'])/3-1).' '.($this->n+1).' 0 R]');
		}
		else {
			$this->_out('/ColorSpace /'.$info['cs']);
			if($info['cs']=='DeviceCMYK') {
				if ($this->PDFA && $this->restrictColorSpace!=3) { $this->Error("PDFA1-b does not permit Images using mixed colour space (".$file.")."); }
				if($info['type']=='jpg') { $this->_out('/Decode [1 0 1 0 1 0 1 0]'); }
			}
			else if ($info['cs']=='DeviceRGB' && ($this->PDFX || ($this->PDFA && $this->restrictColorSpace==3))) { $this->Error("PDFA1-b and PDFX/1-a files do not permit using mixed colour space (".$file.")."); }
		}
		$this->_out('/BitsPerComponent '.$info['bpc']);
		if (isset($info['f']) && $info['f']) { $this->_out('/Filter /'.$info['f']); }
		if(isset($info['parms'])) { $this->_out($info['parms']); }
		if(isset($info['trns']) and is_array($info['trns'])) {
			$trns='';
			for($i=0;$i<count($info['trns']);$i++)
				$trns.=$info['trns'][$i].' '.$info['trns'][$i].' ';
			$this->_out('/Mask ['.$trns.']');
		}
		$this->_out('/Length '.strlen($info['data']).'>>');
		$this->_putstream($info['data']);

		unset($this->images[$file]['data']);
		$this->_out('endobj');
		//Palette
		if($info['cs']=='Indexed') {
			$this->_newobj();
			$pal=($this->compress) ? gzcompress($info['pal']) : $info['pal'];
			$this->_out('<<'.$filter.'/Length '.strlen($pal).'>>');
			$this->_putstream($pal);
			$this->_out('endobj');
		}
	}
}

function _putinfo()
{
	$this->_out('/Producer '.$this->_UTF16BEtextstring('mPDF '.mPDF_VERSION));
	if(!empty($this->title))
		$this->_out('/Title '.$this->_UTF16BEtextstring($this->title));
	if(!empty($this->subject))
		$this->_out('/Subject '.$this->_UTF16BEtextstring($this->subject));
	if(!empty($this->author))
		$this->_out('/Author '.$this->_UTF16BEtextstring($this->author));
	if(!empty($this->keywords))
		$this->_out('/Keywords '.$this->_UTF16BEtextstring($this->keywords));
	if(!empty($this->creator))
		$this->_out('/Creator '.$this->_UTF16BEtextstring($this->creator));

	$z = date('O'); // +0200
	$offset = substr($z,0,3)."'".substr($z,3,2)."'";
	$this->_out('/CreationDate '.$this->_textstring(date('YmdHis').$offset));
	$this->_out('/ModDate '.$this->_textstring(date('YmdHis').$offset));
	if ($this->PDFX) {
		$this->_out('/Trapped/False');
		$this->_out('/GTS_PDFXVersion(PDF/X-1a:2003)');
	}
}

function _putmetadata() {
	$this->_newobj();
	$this->MetadataRoot = $this->n;
	$Producer = 'mPDF '.mPDF_VERSION;
	$z = date('O'); // +0200
	$offset = substr($z,0,3).':'.substr($z,3,2);
	$CreationDate = date('Y-m-d\TH:i:s').$offset;	// 2006-03-10T10:47:26-05:00 2006-06-19T09:05:17Z
	$uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff),
			mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
			mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)  );


	$m = '<?xpacket begin="'.chr(239).chr(187).chr(191).'" id="W5M0MpCehiHzreSzNTczkc9d"?>'."\n";	// begin = FEFF BOM
	$m .= ' <x:xmpmeta xmlns:x="adobe:ns:meta/" x:xmptk="3.1-701">'."\n";
	$m .= '  <rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#">'."\n";
	$m .= '   <rdf:Description rdf:about="uuid:'.$uuid.'" xmlns:pdf="http://ns.adobe.com/pdf/1.3/">'."\n";
	$m .= '    <pdf:Producer>'.$Producer.'</pdf:Producer>'."\n";
	if(!empty($this->keywords)) { $m .= '    <pdf:Keywords>'.$this->keywords.'</pdf:Keywords>'."\n"; }
	$m .= '   </rdf:Description>'."\n";

	$m .= '   <rdf:Description rdf:about="uuid:'.$uuid.'" xmlns:xmp="http://ns.adobe.com/xap/1.0/">'."\n";
	$m .= '    <xmp:CreateDate>'.$CreationDate.'</xmp:CreateDate>'."\n";
	$m .= '    <xmp:ModifyDate>'.$CreationDate.'</xmp:ModifyDate>'."\n";
	$m .= '    <xmp:MetadataDate>'.$CreationDate.'</xmp:MetadataDate>'."\n";
	if(!empty($this->creator)) { $m .= '    <xmp:CreatorTool>'.$this->creator.'</xmp:CreatorTool>'."\n"; }
	$m .= '   </rdf:Description>'."\n";

	// DC elements
	$m .= '   <rdf:Description rdf:about="uuid:'.$uuid.'" xmlns:dc="http://purl.org/dc/elements/1.1/">'."\n";
	$m .= '    <dc:format>application/pdf</dc:format>'."\n";
	if(!empty($this->title)) {
		$m .= '    <dc:title>
     <rdf:Alt>
      <rdf:li xml:lang="x-default">'.$this->title.'</rdf:li>
     </rdf:Alt>
    </dc:title>'."\n";
	}
	if(!empty($this->keywords)) {
		$m .= '    <dc:subject>
     <rdf:Bag>
      <rdf:li>'.$this->keywords.'</rdf:li>
     </rdf:Bag>
    </dc:subject>'."\n";
	}
	if(!empty($this->subject)) {
		$m .= '    <dc:description>
     <rdf:Alt>
      <rdf:li xml:lang="x-default">'.$this->subject.'</rdf:li>
     </rdf:Alt>
    </dc:description>'."\n";
	}
	if(!empty($this->author)) {
		$m .= '    <dc:creator>
     <rdf:Seq>
      <rdf:li>'.$this->author.'</rdf:li>
     </rdf:Seq>
    </dc:creator>'."\n";
	}
	$m .= '   </rdf:Description>'."\n";


	// This bit is specific to PDFX-1a
	if ($this->PDFX) {
		$m .= '   <rdf:Description rdf:about="uuid:'.$uuid.'" xmlns:pdfx="http://ns.adobe.com/pdfx/1.3/" pdfx:Apag_PDFX_Checkup="1.3" pdfx:GTS_PDFXConformance="PDF/X-1a:2003" pdfx:GTS_PDFXVersion="PDF/X-1:2003"/>'."\n";
	}

	// This bit is specific to PDFA-1b
	else if ($this->PDFA) {
		$m .= '   <rdf:Description rdf:about="uuid:'.$uuid.'" xmlns:pdfaid="http://www.aiim.org/pdfa/ns/id/" >'."\n";
		$m .= '    <pdfaid:part>1</pdfaid:part>'."\n";
		$m .= '    <pdfaid:conformance>B</pdfaid:conformance>'."\n";
		$m .= '    <pdfaid:amd>2005</pdfaid:amd>'."\n";
		$m .= '   </rdf:Description>'."\n";
	}

	$m .= '   <rdf:Description rdf:about="uuid:'.$uuid.'" xmlns:xmpMM="http://ns.adobe.com/xap/1.0/mm/">'."\n";
	$m .= '    <xmpMM:DocumentID>uuid:'.$uuid.'</xmpMM:DocumentID>'."\n";
	$m .= '   </rdf:Description>'."\n";
	$m .= '  </rdf:RDF>'."\n";
	$m .= ' </x:xmpmeta>'."\n";
	$m .= str_repeat(str_repeat(' ',100)."\n",20);	// 2-4kB whitespace padding required
	$m .= '<?xpacket end="w"?>';	// "r" read only
	$this->_out('<</Type/Metadata/Subtype/XML/Length '.strlen($m).'>>');
	$this->_putstream($m);
	$this->_out('endobj');
}

function _putoutputintent() {
	$this->_newobj();
	$this->OutputIntentRoot = $this->n;
	$this->_out('<</Type /OutputIntent');

	if ($this->PDFA) {
		$this->_out('/S /GTS_PDFA1');
		if ($this->ICCProfile) {
			$this->_out('/Info ('.preg_replace('/_/',' ',$this->ICCProfile).')');
			$this->_out('/OutputConditionIdentifier (Custom)');
			$this->_out('/OutputCondition ()');
		}
		else {
			$this->_out('/Info (sRGB IEC61966-2.1)');
			$this->_out('/OutputConditionIdentifier (sRGB IEC61966-2.1)');
			$this->_out('/OutputCondition ()');
		}
		$this->_out('/DestOutputProfile '.($this->n+1).' 0 R');
	}
	else if ($this->PDFX) {	// always a CMYK profile
		$this->_out('/S /GTS_PDFX');
		if ($this->ICCProfile) {
			$this->_out('/Info ('.preg_replace('/_/',' ',$this->ICCProfile).')');
			$this->_out('/OutputConditionIdentifier (Custom)');
			$this->_out('/OutputCondition ()');
			$this->_out('/DestOutputProfile '.($this->n+1).' 0 R');
		}
		else {
			$this->_out('/Info (CGATS TR 001)');
			$this->_out('/OutputConditionIdentifier (CGATS TR 001)');
			$this->_out('/OutputCondition (CGATS TR 001 (SWOP))');
			$this->_out('/RegistryName (http://www.color.org)');
		}
	}
	$this->_out('>>');
	$this->_out('endobj');

	if ($this->PDFX && !$this->ICCProfile) { return; } // no ICCProfile embedded

	$this->_newobj();
	if ($this->ICCProfile)
		$s = file_get_contents(_MPDF_PATH.'iccprofiles/'.$this->ICCProfile.'.icc');
	else
		$s = file_get_contents(_MPDF_PATH.'iccprofiles/sRGB_IEC61966-2-1.icc');
	if ($this->compress) { $s = gzcompress($s); }
	$this->_out('<<');
	if ($this->PDFX || ($this->PDFA && $this->restrictColorSpace == 3)) { $this->_out('/N 4'); }
	else { $this->_out('/N 3'); }
	if ($this->compress)
		$this->_out('/Filter /FlateDecode ');
	$this->_out('/Length '.strlen($s).'>>');
	$this->_putstream($s);
	$this->_out('endobj');
}


function _putcatalog() {
	$this->_out('/Type /Catalog');
	$this->_out('/Pages 1 0 R');
	if($this->ZoomMode=='fullpage')	$this->_out('/OpenAction [3 0 R /Fit]');
	elseif($this->ZoomMode=='fullwidth') $this->_out('/OpenAction [3 0 R /FitH null]');
	elseif($this->ZoomMode=='real')	$this->_out('/OpenAction [3 0 R /XYZ null null 1]');
	elseif(!is_string($this->ZoomMode))	$this->_out('/OpenAction [3 0 R /XYZ null null '.($this->ZoomMode/100).']');
	else	$this->_out('/OpenAction [3 0 R /XYZ null null null]');
	if($this->LayoutMode=='single')	$this->_out('/PageLayout /SinglePage');
	elseif($this->LayoutMode=='continuous')	$this->_out('/PageLayout /OneColumn');
	elseif($this->LayoutMode=='twoleft')	$this->_out('/PageLayout /TwoColumnLeft');
	elseif($this->LayoutMode=='tworight')	$this->_out('/PageLayout /TwoColumnRight');
	elseif($this->LayoutMode=='two') {
	  if ($this->mirrorMargins) { $this->_out('/PageLayout /TwoColumnRight'); }
	  else { $this->_out('/PageLayout /TwoColumnLeft'); }
	}

	if(is_int(strpos($this->DisplayPreferences,'FullScreen'))) $this->_out('/PageMode /FullScreen');

	// Metadata
	if ($this->PDFA || $this->PDFX) {
		$this->_out('/Metadata '.$this->MetadataRoot.' 0 R');
	}
	// OutputIntents
	if ($this->PDFA || $this->PDFX || $this->ICCProfile) {
		$this->_out('/OutputIntents ['.$this->OutputIntentRoot.' 0 R]');
	}

	if ( isset($this->js) ) {
		$this->_out('/Names << /JavaScript '.($this->n_js).' 0 R >> ');
	}

	if($this->DisplayPreferences || $this->directionality == 'rtl' || $this->mirrorMargins) {
		$this->_out('/ViewerPreferences<<');
		if(is_int(strpos($this->DisplayPreferences,'HideMenubar'))) $this->_out('/HideMenubar true');
		if(is_int(strpos($this->DisplayPreferences,'HideToolbar'))) $this->_out('/HideToolbar true');
		if(is_int(strpos($this->DisplayPreferences,'HideWindowUI'))) $this->_out('/HideWindowUI true');
		if(is_int(strpos($this->DisplayPreferences,'DisplayDocTitle'))) $this->_out('/DisplayDocTitle true');
		if(is_int(strpos($this->DisplayPreferences,'CenterWindow'))) $this->_out('/CenterWindow true');
		if(is_int(strpos($this->DisplayPreferences,'FitWindow'))) $this->_out('/FitWindow true');
		// /PrintScaling is PDF 1.6 spec.
		if(is_int(strpos($this->DisplayPreferences,'NoPrintScaling')) && !$this->PDFA && !$this->PDFX)
			$this->_out('/PrintScaling /None');
		if($this->directionality == 'rtl') $this->_out('/Direction /R2L');
		// /Duplex is PDF 1.7 spec.
		if($this->mirrorMargins && !$this->PDFA && !$this->PDFX) {
			// if ($this->DefOrientation=='P') $this->_out('/Duplex /DuplexFlipShortEdge');
			$this->_out('/Duplex /DuplexFlipLongEdge');	// PDF v1.7+
		}
		$this->_out('>>');
	}
	// mPDF 5.6.01
	if($this->open_layer_pane && ($this->hasOC || count($this->layers)))
		$this->_out('/PageMode /UseOC');

	// mPDF 5.6.01
	if ($this->hasOC || count($this->layers)) {
		$p = $v = $h = $l = $as = '';
		if ($this->hasOC) {
			if (($this->hasOC & 1) == 1) $p=$this->n_ocg_print.' 0 R';
			if (($this->hasOC & 2) == 2) $v=$this->n_ocg_view.' 0 R';
			if (($this->hasOC & 4) == 4) $h=$this->n_ocg_hidden.' 0 R';
			$as="<</Event /Print /OCGs [$p $v $h] /Category [/Print]>> <</Event /View /OCGs [$p $v $h] /Category [/View]>>";
		}

		if(count($this->layers)) {
			foreach($this->layers as $layer) {
				$l .= $layer['n'].' 0 R ';
			}
		}
		$this->_out("/OCProperties <</OCGs [$p $v $h $l] /D <</ON [$p $l] /OFF [$v $h] ");
		$this->_out("/Order [$v $p $h $l] ");
		if ($as) $this->_out("/AS [$as] ");
		$this->_out(">>>>");

	}

}

// Inactive function left for backwards compatability
function SetUserRights($enable=true, $annots="", $form="", $signature="") {
	// Does nothing
}

function _enddoc() {
	// Remove references to unused fonts (usually default font)
	foreach($this->fonts as $fk=>$font) {
	   if (!$font['used'] && ($font['type']=='TTF')) {
		if ($font['sip'] || $font['smp']) {
			foreach($font['subsetfontids'] AS $k => $fid) {
				foreach($this->pages AS $pn=>$page) {
					$this->pages[$pn] = preg_replace('/\s\/F'.$fid.' \d[\d.]* Tf\s/is',' ',$this->pages[$pn]);
				}
			}
		}
		else {
				foreach($this->pages AS $pn=>$page) {
					$this->pages[$pn] = preg_replace('/\s\/F'.$font['i'].' \d[\d.]* Tf\s/is',' ',$this->pages[$pn]);
				}
		}
	   }
	}

	// mPDF 5.6.01 - LAYERS
	if (count($this->layers)) {
	  foreach($this->pages AS $pn=>$page) {
		preg_match_all('/\/OCZ-index \/ZI(\d+) BDC(.*?)(EMCZ)-index/is',$this->pages[$pn],$m1);
		preg_match_all('/\/OCBZ-index \/ZI(\d+) BDC(.*?)(EMCBZ)-index/is',$this->pages[$pn],$m2);
		preg_match_all('/\/OCGZ-index \/ZI(\d+) BDC(.*?)(EMCGZ)-index/is',$this->pages[$pn],$m3);
		$m = array();
		for ($i=0;$i<4;$i++) {
			$m[$i] = array_merge($m1[$i],$m2[$i],$m3[$i]);
		}
		if (count($m[0])) {
			$sortarr = array();
			for($i=0;$i<count($m[0]);$i++) {
				$key = $m[1][$i]*2;
				if ($m[3][$i]=='EMCZ') $key +=2;	// background first then gradient then normal
				else if ($m[3][$i]=='EMCGZ') $key +=1;
				$sortarr[$i] = $key;
			}
			asort($sortarr);
			foreach($sortarr AS $i=>$k) {
				$this->pages[$pn] = str_replace($m[0][$i],'',$this->pages[$pn] );
				$this->pages[$pn] .= "\n".$m[0][$i]."\n";
			}
			$this->pages[$pn] = preg_replace('/\/OC[BG]{0,1}Z-index \/ZI(\d+) BDC/is','/OC /ZI\\1 BDC ',$this->pages[$pn]);
			$this->pages[$pn] = preg_replace('/EMC[BG]{0,1}Z-index/is','EMC',$this->pages[$pn]);
		}
	  }
	}

	$this->_putpages();

	$this->_putresources();
	//Info
	$this->_newobj();
	$this->InfoRoot = $this->n;
	$this->_out('<<');
	$this->_putinfo();
	$this->_out('>>');
	$this->_out('endobj');

	// METADATA
	if ($this->PDFA || $this->PDFX) { $this->_putmetadata(); }
	// OUTPUTINTENT
	if ($this->PDFA || $this->PDFX || $this->ICCProfile) { $this->_putoutputintent(); }

	//Catalog
	$this->_newobj();
	$this->_out('<<');
	$this->_putcatalog();
	$this->_out('>>');
	$this->_out('endobj');
	//Cross-ref
	$o=strlen($this->buffer);
	$this->_out('xref');
	$this->_out('0 '.($this->n+1));
	$this->_out('0000000000 65535 f ');
	for($i=1; $i <= $this->n ; $i++)
		$this->_out(sprintf('%010d 00000 n ',$this->offsets[$i]));
	//Trailer
	$this->_out('trailer');
	$this->_out('<<');
	$this->_puttrailer();
	$this->_out('>>');
	$this->_out('startxref');
	$this->_out($o);

	$this->buffer .= '%%EOF';
	$this->state=3;
}

function _beginpage($orientation,$mgl='',$mgr='',$mgt='',$mgb='',$mgh='',$mgf='',$ohname='',$ehname='',$ofname='',$efname='',$ohvalue=0,$ehvalue=0,$ofvalue=0,$efvalue=0,$pagesel='',$newformat='') {
	if (!($pagesel && $this->page==1 && (sprintf("%0.4f", $this->y)==sprintf("%0.4f", $this->tMargin)))) {
		$this->page++;
		$this->pages[$this->page]='';
	}
	$this->state=2;
	$resetHTMLHeadersrequired = false;


	if ($newformat) { $this->_setPageSize($newformat, $orientation); }

	//Page orientation
	if(!$orientation)
		$orientation=$this->DefOrientation;
	else {
		$orientation=strtoupper(substr($orientation,0,1));
		if($orientation!=$this->DefOrientation)
			$this->OrientationChanges[$this->page]=true;
	}
	if($orientation!=$this->CurOrientation || $newformat) {

		//Change orientation
		if($orientation=='P') {
			$this->wPt=$this->fwPt;
			$this->hPt=$this->fhPt;
			$this->w=$this->fw;
			$this->h=$this->fh;
		   if (($this->forcePortraitHeaders || $this->forcePortraitMargins) && $this->DefOrientation=='P') {
			$this->tMargin = $this->orig_tMargin;
			$this->bMargin = $this->orig_bMargin;
			$this->DeflMargin = $this->orig_lMargin;
			$this->DefrMargin = $this->orig_rMargin;
			$this->margin_header = $this->orig_hMargin;
			$this->margin_footer = $this->orig_fMargin;
		   }
		}
		else {
			$this->wPt=$this->fhPt;
			$this->hPt=$this->fwPt;
			$this->w=$this->fh;
			$this->h=$this->fw;
		   if (($this->forcePortraitHeaders || $this->forcePortraitMargins) && $this->DefOrientation=='P') {
			$this->tMargin = $this->orig_lMargin;
			$this->bMargin = $this->orig_rMargin;
			$this->DeflMargin = $this->orig_bMargin;
			$this->DefrMargin = $this->orig_tMargin;
			$this->margin_header = $this->orig_hMargin;
			$this->margin_footer = $this->orig_fMargin;
		   }

		}
		$this->CurOrientation=$orientation;
		$this->ResetMargins();
		$this->pgwidth = $this->w - $this->lMargin - $this->rMargin;
		$this->PageBreakTrigger=$this->h-$this->bMargin;
	}

	$this->pageDim[$this->page]['w']=$this->w ;
	$this->pageDim[$this->page]['h']=$this->h ;

	$this->pageDim[$this->page]['outer_width_LR'] = isset($this->page_box['outer_width_LR']) ? $this->page_box['outer_width_LR'] : 0;
	$this->pageDim[$this->page]['outer_width_TB'] = isset($this->page_box['outer_width_TB']) ? $this->page_box['outer_width_TB'] : 0;
	if (!isset($this->page_box['outer_width_LR']) && !isset($this->page_box['outer_width_TB'])) {
		$this->pageDim[$this->page]['bleedMargin'] = 0;
	}
	else if ($this->bleedMargin <= $this->page_box['outer_width_LR'] && $this->bleedMargin <= $this->page_box['outer_width_TB']) {
		$this->pageDim[$this->page]['bleedMargin'] = $this->bleedMargin;
	}
	else {
		$this->pageDim[$this->page]['bleedMargin'] = min($this->page_box['outer_width_LR'], $this->page_box['outer_width_TB'])-0.01;
	}

	// If Page Margins are re-defined
	// strlen()>0 is used to pick up (integer) 0, (string) '0', or set value
	if ((strlen($mgl)>0 && $this->DeflMargin != $mgl) || (strlen($mgr)>0 && $this->DefrMargin != $mgr) || (strlen($mgt)>0 && $this->tMargin != $mgt) || (strlen($mgb)>0 && $this->bMargin != $mgb) || (strlen($mgh)>0 && $this->margin_header!=$mgh) || (strlen($mgf)>0 && $this->margin_footer!=$mgf)) {
		if (strlen($mgl)>0)  $this->DeflMargin = $mgl;
		if (strlen($mgr)>0)  $this->DefrMargin = $mgr;
		if (strlen($mgt)>0)  $this->tMargin = $mgt;
		if (strlen($mgb)>0)  $this->bMargin = $mgb;
		if (strlen($mgh)>0)  $this->margin_header=$mgh;
		if (strlen($mgf)>0)  $this->margin_footer=$mgf;
		$this->ResetMargins();
		$this->SetAutoPageBreak($this->autoPageBreak,$this->bMargin);
		$this->pgwidth = $this->w - $this->lMargin - $this->rMargin;
	}

	$this->ResetMargins();
	$this->pgwidth = $this->w - $this->lMargin - $this->rMargin;
	$this->SetAutoPageBreak($this->autoPageBreak,$this->bMargin);

	// Reset column top margin
	$this->y0 = $this->tMargin;

	$this->x=$this->lMargin;
	$this->y=$this->tMargin;
	$this->FontFamily='';

	// HEADERS AND FOOTERS
	if ($ohvalue<0 || strtoupper($ohvalue)=='OFF') {
		$this->HTMLHeader = '';
		$this->headerDetails['odd'] = array();
	}
	else if ($ohname && $ohvalue>0) {
		if (isset($this->pageheaders[$ohname])) { $this->headerDetails['odd'] = $this->pageheaders[$ohname]; }
		else if ($ohname!='_default') { $this->headerDetails['odd'] = array(); }
		$this->HTMLHeader = '';
	}

	if ($ehvalue<0 || strtoupper($ehvalue)=='OFF') {
		$this->HTMLHeaderE = '';
		$this->headerDetails['even'] = array();
	}
	else if ($ehname && $ehvalue>0) {
		if (isset($this->pageheaders[$ehname])) { $this->headerDetails['even'] = $this->pageheaders[$ehname]; }
		else if ($ehname!='_default') { $this->headerDetails['even'] = array(); }
		$this->HTMLHeaderE = '';
	}

	if ($ofvalue<0 || strtoupper($ofvalue)=='OFF') {
		$this->HTMLFooter = '';
		$this->footerDetails['odd'] = array();
	}
	else if ($ofname && $ofvalue>0) {
		if (isset($this->pagefooters[$ofname])) { $this->footerDetails['odd'] = $this->pagefooters[$ofname]; }
		else if ($ofname!='_default') { $this->footerDetails['odd'] = array(); }
		$this->HTMLFooter = '';
	}

	if ($efvalue<0 || strtoupper($efvalue)=='OFF') {
		$this->HTMLFooterE = '';
		$this->footerDetails['even'] = array();
	}
	else if ($efname && $efvalue>0) {
		if (isset($this->pagefooters[$efname])) { $this->footerDetails['even'] = $this->pagefooters[$efname]; }
		else if ($efname!='_default') { $this->footerDetails['even'] = array(); }
		$this->HTMLFooterE = '';
	}



	if (($this->mirrorMargins) && (($this->page)%2==0)) {	// EVEN
		$this->_setAutoHeaderHeight($this->headerDetails['even'], $this->HTMLHeaderE);
		$this->_setAutoFooterHeight($this->footerDetails['even'], $this->HTMLFooterE);
	}
	else {	// ODD or DEFAULT
		$this->_setAutoHeaderHeight($this->headerDetails['odd'], $this->HTMLHeader);
		$this->_setAutoFooterHeight($this->footerDetails['odd'], $this->HTMLFooter);
	}
	// Reset column top margin
	$this->y0 = $this->tMargin;

	$this->x=$this->lMargin;
	$this->y=$this->tMargin;
}

/*
 * Blue Liquid Designs Edit
 * Add isset() to if statement as it throws errors
 */
function _setAutoHeaderHeight(&$det, &$htmlh) {
  if ($this->setAutoTopMargin=='pad') {
	if (isset($htmlh['h']) && $htmlh['h']) { $h = $htmlh['h']; }
	else if ($det) { $h = $this->_getHFHeight($det,'H'); }
	else { $h = 0; }
	$this->tMargin = $this->margin_header + $h + $this->orig_tMargin;
  }
  else if ($this->setAutoTopMargin=='stretch') {
	if (isset($htmlh['h']) && $htmlh['h']) { $h = $htmlh['h']; }
	else if ($det) { $h = $this->_getHFHeight($det,'H'); }
	else { $h = 0; }
	$this->tMargin = max($this->orig_tMargin, $this->margin_header + $h + $this->autoMarginPadding);
  }
}


/*
 * Blue Liquid Designs Edit
 * Add isset() to if statement as it throws errors
 */
function _setAutoFooterHeight(&$det, &$htmlf) {
  if ($this->setAutoBottomMargin=='pad') {
	if (isset($htmlf['h']) && $htmlf['h']) { $h = $htmlf['h']; }
	else if ($det) { $h = $this->_getHFHeight($det,'F'); }
	else { $h = 0; }
	$this->bMargin = $this->margin_footer + $h + $this->orig_bMargin;
	$this->PageBreakTrigger=$this->h-$this->bMargin ;
  }
  else if ($this->setAutoBottomMargin=='stretch') {
	if (isset($htmlf['h']) && $htmlf['h']) { $h = $htmlf['h']; }
	else if ($det) { $h = $this->_getHFHeight($det,'F'); }
	else { $h = 0; }
	$this->bMargin = max($this->orig_bMargin, $this->margin_footer + $h + $this->autoMarginPadding);
	$this->PageBreakTrigger=$this->h-$this->bMargin ;
  }
}

function _getHFHeight(&$det,$end) {
	$h = 0;
	if(count($det)) {
		foreach(array('L','C','R') AS $pos) {
		  if (isset($det[$pos]['content']) && $det[$pos]['content']) {
			if (isset($det[$pos]['font-size']) && $det[$pos]['font-size']) { $hfsz = $det[$pos]['font-size']; }
			else { $hfsz = $this->default_font_size; }
			$h = max($h,$hfsz/_MPDFK);
		  }
		}
		if ($det['line'] && $end=='H') { $h += $h/_MPDFK*$this->header_line_spacing; }
		else if ($det['line'] && $end=='F') { $h += $h/_MPDFK*$this->footer_line_spacing; }
   	}
	return $h;
}


function _endpage() {

	if($this->visibility!='visible')
		$this->SetVisibility('visible');
	$this->EndLayer();	// mPDF 5.6.01
	//End of page contents
	$this->state=1;
}

function _newobj($obj_id=false,$onlynewobj=false) {
		if (!$obj_id) {
			$obj_id = ++$this->n;
		}
		//Begin a new object
		if (!$onlynewobj) {
			$this->offsets[$obj_id] = strlen($this->buffer);
			$this->_out($obj_id.' 0 obj');
			$this->_current_obj_id = $obj_id; // for later use with encryption
		}
}

function _dounderline($x,$y,$txt) {
	// Now print line exactly where $y secifies - called from Text() and Cell() - adjust  position there
	// WORD SPACING
      $w =($this->GetStringWidth($txt)*_MPDFK) + ($this->charspacing * mb_strlen( $txt, $this->mb_enc ))
		 + ( $this->ws * mb_substr_count( $txt, ' ', $this->mb_enc ));
	//Draw a line
	return sprintf('%.3F %.3F m %.3F %.3F l S',$x*_MPDFK,($this->h-$y)*_MPDFK,($x*_MPDFK)+$w,($this->h-$y)*_MPDFK);
}


function _imageError($file, $firsttime, $msg) {
	// Save re-trying image URL's which have already failed
	$this->failedimages[$file] = true;
	if ($firsttime && ($this->showImageErrors || $this->debug)) {
			$this->Error("IMAGE Error (".$file."): ".$msg);
	}
	return false;
}


function _getImage(&$file, $firsttime=true, $allowvector=true, $orig_srcpath=false) {
	// firsttime i.e. whether to add to this->images - use false when calling iteratively
	// Image Data passed directly as var:varname
	if (preg_match('/var:\s*(.*)/',$file, $v)) {
		$data = $this->$v[1];
		$file = md5($data);
	}
	// mPDF 5.5.13
	if (preg_match('/data:image\/(gif|jpeg|png);base64,(.*)/',$file, $v)) {
		$type = $v[1];
		$data = base64_decode($v[2]);
		$file = md5($data);
	}

	// mPDF 5.6.02
	if ($firsttime && $file && substr($file,0,5)!='data:') { $file = urlencode_part($file); }
	if ($firsttime && $orig_srcpath && substr($orig_srcpath,0,5)!='data:') { $orig_srcpath = urlencode_part($orig_srcpath); }

	$ppUx = 0;
	if ($orig_srcpath && isset($this->images[$orig_srcpath])) { $file=$orig_srcpath; return $this->images[$orig_srcpath]; }
	if (isset($this->images[$file])) { return $this->images[$file]; }
	else if ($orig_srcpath && isset($this->formobjects[$orig_srcpath])) { $file=$orig_srcpath; return $this->formobjects[$file]; }
	else if (isset($this->formobjects[$file])) { return $this->formobjects[$file]; }
	// Save re-trying image URL's which have already failed
	else if ($firsttime && isset($this->failedimages[$file])) { return $this->_imageError($file, $firsttime, ''); }
	if (empty($data)) {
		$type = '';
		$data = '';

 		if ($orig_srcpath && $this->basepathIsLocal && $check = @fopen($orig_srcpath,"rb")) {
			fclose($check);
			$file=$orig_srcpath;
			$data = file_get_contents($file);
			$type = $this->_imageTypeFromString($data);
		}
		if (!$data && $check = @fopen($file,"rb")) {
			fclose($check);
			$data = file_get_contents($file);
			$type = $this->_imageTypeFromString($data);
		}
		if ((!$data || !$type) && !ini_get('allow_url_fopen') ) {	// only worth trying if remote file and !ini_get('allow_url_fopen')
			$this->file_get_contents_by_socket($file, $data);	// needs full url?? even on local (never needed for local)
			if ($data) { $type = $this->_imageTypeFromString($data); }
		}
		if ((!$data || !$type) && !ini_get('allow_url_fopen') && function_exists("curl_init")) {
			$this->file_get_contents_by_curl($file, $data);		// needs full url?? even on local (never needed for local)
			if ($data) { $type = $this->_imageTypeFromString($data); }
		}
		if(!data)
		{
			/* Blue Liquid Designs Edit
			   Ensure Wordpress compatibility
			 */
				if( !class_exists( 'WP_Http' ) ) {
					include_once( ABSPATH . WPINC. '/class-http.php' );
				}
				$request = new WP_Http();
				$result = $request->request($full_url);
				if ( !is_wp_error($result) ) {
					$data = $result['body'];
					$type = $this->_imageTypeFromString($data);
				}
		}

	}
	if (!$data) { return $this->_imageError($file, $firsttime, 'Could not find image file'); }
	if (empty($type)) { $type = $this->_imageTypeFromString($data); }
	if (($type == 'wmf' || $type == 'svg') && !$allowvector) { return $this->_imageError($file, $firsttime, 'WMF or SVG image file not supported in this context'); }

	// SVG
	if ($type == 'svg') {
		if (!class_exists('SVG', false)) { include(_MPDF_PATH .'classes/svg.php'); }
		$svg = new SVG($this);
		$family=$this->FontFamily;
		$style=$this->FontStyle;
		$size=$this->FontSizePt;
		$info = $svg->ImageSVG($data);
		//Restore font
		if($family) $this->SetFont($family,$style,$size,false);
		if (!$info) { return $this->_imageError($file, $firsttime, 'Error parsing SVG file'); }
		$info['type']='svg';
		$info['i']=count($this->formobjects)+1;
		$this->formobjects[$file]=$info;
		return $info;
	}

	// JPEG
	if ($type == 'jpeg' || $type == 'jpg') {
		$hdr = $this->_jpgHeaderFromString($data);
		if (!$hdr) { return $this->_imageError($file, $firsttime, 'Error parsing JPG header'); }
		$a = $this->_jpgDataFromHeader($hdr);
		$j = strpos($data,'JFIF');
		if ($j) {
			//Read resolution
			$unitSp=ord(substr($data,($j+7),1));
			if ($unitSp > 0) {
				$ppUx=$this->_twobytes2int(substr($data,($j+8),2));	// horizontal pixels per meter, usually set to zero
				if ($unitSp == 2) {	// = dots per cm (if == 1 set as dpi)
					$ppUx=round($ppUx/10 *25.4);
				}
			}
		}
		if ($a[2] == 'DeviceCMYK' && (($this->PDFA && $this->restrictColorSpace!=3) || $this->restrictColorSpace==2)) {
			// convert to RGB image
			if (!function_exists("gd_info")) { $this->Error("JPG image may not use CMYK color space (".$file.")."); }
			if ($this->PDFA && !$this->PDFAauto) { $this->PDFAXwarnings[] = "JPG image may not use CMYK color space - ".$file." - (Image converted to RGB. NB This will alter the colour profile of the image.)"; }
			$im = @imagecreatefromstring($data);
			if ($im) {
				$tempfile = _MPDF_TEMP_PATH.'_tempImgPNG'.RAND(1,10000).'.png';
				imageinterlace($im, false);
				$check = @imagepng($im, $tempfile);
				if (!$check) { return $this->_imageError($file, $firsttime, 'Error creating temporary file ('.$tempfile.') whilst using GD library to parse JPG(CMYK) image'); }
				$info = $this->_getImage($tempfile, false);
				if (!$info) { return $this->_imageError($file, $firsttime, 'Error parsing temporary file ('.$tempfile.') created with GD library to parse JPG(CMYK) image'); }
				imagedestroy($im);
				unlink($tempfile);
				$info['type']='jpg';
				if ($firsttime) {
					$info['i']=count($this->images)+1;
					$this->images[$file]=$info;
				}
				return $info;
			}
			else { return $this->_imageError($file, $firsttime, 'Error creating GD image file from JPG(CMYK) image'); }
		}
		else if ($a[2] == 'DeviceRGB' && ($this->PDFX || $this->restrictColorSpace==3)) {
			// Convert to CMYK image stream - nominally returned as type='png'
			$info = $this->_convImage($data, $a[2], 'DeviceCMYK', $a[0], $a[1], $ppUx, false);
			if (($this->PDFA && !$this->PDFAauto) || ($this->PDFX && !$this->PDFXauto)) { $this->PDFAXwarnings[] = "JPG image may not use RGB color space - ".$file." - (Image converted to CMYK. NB This will alter the colour profile of the image.)"; }
		}
		else if (($a[2] == 'DeviceRGB' || $a[2] == 'DeviceCMYK') && $this->restrictColorSpace==1) {
			// Convert to Grayscale image stream - nominally returned as type='png'
			$info = $this->_convImage($data, $a[2], 'DeviceGray', $a[0], $a[1], $ppUx, false);
		}
		else {
			$info = array('w'=>$a[0],'h'=>$a[1],'cs'=>$a[2],'bpc'=>$a[3],'f'=>'DCTDecode','data'=>$data, 'type'=>'jpg');
			if ($ppUx) { $info['set-dpi'] = $ppUx; }
		}
		if (!$info) { return $this->_imageError($file, $firsttime, 'Error parsing or converting JPG image'); }

		if ($firsttime) {
			$info['i']=count($this->images)+1;
			$this->images[$file]=$info;
		}
		return $info;
	}

	// PNG
	else if ($type == 'png') {
		//Check signature
		if(substr($data,0,8)!=chr(137).'PNG'.chr(13).chr(10).chr(26).chr(10)) {
			return $this->_imageError($file, $firsttime, 'Error parsing PNG identifier');
		}
		//Read header chunk
		if(substr($data,12,4)!='IHDR') {
			return $this->_imageError($file, $firsttime, 'Incorrect PNG file (no IHDR block found)');
		}

		$w=$this->_fourbytes2int(substr($data,16,4));
		$h=$this->_fourbytes2int(substr($data,20,4));
		$bpc=ord(substr($data,24,1));
		$errpng = false;
		$pngalpha = false;
		if($bpc>8) { $errpng = 'not 8-bit depth'; }
		$ct=ord(substr($data,25,1));
		if($ct==0) { $colspace='DeviceGray'; }
		elseif($ct==2) { $colspace='DeviceRGB'; }
		elseif($ct==3) { $colspace='Indexed'; }
		elseif($ct==4) { $colspace='DeviceGray';  $errpng = 'alpha channel'; $pngalpha = true; }
		else { $colspace='DeviceRGB'; $errpng = 'alpha channel'; $pngalpha = true; }
		if(ord(substr($data,26,1))!=0) { $errpng = 'compression method'; }
		if(ord(substr($data,27,1))!=0) { $errpng = 'filter method'; }
		if(ord(substr($data,28,1))!=0) { $errpng = 'interlaced file'; }
		$j = strpos($data,'pHYs');
		if ($j) {
			//Read resolution
			$unitSp=ord(substr($data,($j+12),1));
			if ($unitSp == 1) {
				$ppUx=$this->_fourbytes2int(substr($data,($j+4),4));	// horizontal pixels per meter, usually set to zero
				$ppUx=round($ppUx/1000 *25.4);
			}
		}
		if (($colspace == 'DeviceRGB' || $colspace == 'Indexed') && ($this->PDFX || $this->restrictColorSpace==3)) {
			// Convert to CMYK image stream - nominally returned as type='png'
			$info = $this->_convImage($data, $colspace, 'DeviceCMYK', $w, $h, $ppUx, $pngalpha);
			if (($this->PDFA && !$this->PDFAauto) || ($this->PDFX && !$this->PDFXauto)) { $this->PDFAXwarnings[] = "PNG image may not use RGB color space - ".$file." - (Image converted to CMYK. NB This will alter the colour profile of the image.)"; }
		}
		else if (($colspace == 'DeviceRGB' || $colspace == 'Indexed') && $this->restrictColorSpace==1) {
			// Convert to Grayscale image stream - nominally returned as type='png'
			$info = $this->_convImage($data, $colspace, 'DeviceGray', $w, $h, $ppUx, $pngalpha);
		}
		else if (($this->PDFA || $this->PDFX) && $pngalpha) {
			// Remove alpha channel
			if ($this->restrictColorSpace==1) {	// Grayscale
				$info = $this->_convImage($data, $colspace, 'DeviceGray', $w, $h, $ppUx, $pngalpha);
			}
			else if ($this->restrictColorSpace==3) {	// CMYK
				$info = $this->_convImage($data, $colspace, 'DeviceCMYK', $w, $h, $ppUx, $pngalpha);
			}
			else if ($this->PDFA ) {	// RGB
				$info = $this->_convImage($data, $colspace, 'DeviceRGB', $w, $h, $ppUx, $pngalpha);
			}
			if (($this->PDFA && !$this->PDFAauto) || ($this->PDFX && !$this->PDFXauto)) { $this->PDFAXwarnings[] = "Transparency (alpha channel) not permitted in PDFA or PDFX files - ".$file." - (Image converted to one without transparency.)"; }
		}
		else if ($errpng || $pngalpha) {
			if (function_exists('gd_info')) { $gd = gd_info(); }
			else {$gd = array(); }
			if (!isset($gd['PNG Support'])) { return $this->_imageError($file, $firsttime, 'GD library required for PNG image ('.$errpng.')'); }
			$im = imagecreatefromstring($data);
			if (!$im) { return $this->_imageError($file, $firsttime, 'Error creating GD image from PNG file ('.$errpng.')'); }
			$w = imagesx($im);
			$h = imagesy($im);
			if ($im) {
			   $tempfile = _MPDF_TEMP_PATH.'_tempImgPNG'.RAND(1,10000).'.png';
			   // Alpha channel set
			   if ($pngalpha) {
				if ($this->PDFA) { $this->Error("PDFA1-b does not permit images with alpha channel transparency (".$file.")."); }
				$imgalpha = imagecreate($w, $h);
				// generate gray scale pallete
				for ($c = 0; $c < 256; ++$c) { ImageColorAllocate($imgalpha, $c, $c, $c); }
				// extract alpha channel
				$gammacorr = 2.2;	// gamma correction
				for ($xpx = 0; $xpx < $w; ++$xpx) {
					for ($ypx = 0; $ypx < $h; ++$ypx) {
						//$colorindex = imagecolorat($im, $xpx, $ypx);
						//$col = imagecolorsforindex($im, $colorindex);
						//$gamma2 = (pow((((127 - $col['alpha']) * 255 / 127) / 255), $gammacorr) * 255);
						$alpha = (imagecolorat($im, $xpx, $ypx) & 0x7F000000) >> 24;
						if ($alpha < 127) {
							if ($alpha==0) { $gamma = 255; }
							else
								$gamma = (pow((((127 - $alpha) * 255 / 127) / 255), $gammacorr) * 255);
							imagesetpixel($imgalpha, $xpx, $ypx, $gamma);
						}
					}
				}
				// create temp alpha file
	 		 	$tempfile_alpha = _MPDF_TEMP_PATH.'_tempMskPNG'.RAND(1,10000).'.png';
				if (!is_writable($tempfile_alpha)) {
					ob_start();
					$check = @imagepng($imgalpha);
					if (!$check) { return $this->_imageError($file, $firsttime, 'Error creating temporary image object whilst using GD library to parse PNG image'); }
					imagedestroy($imgalpha);
					$this->_tempimg = ob_get_contents();
					$this->_tempimglnk = 'var:_tempimg';
					ob_end_clean();
					// extract image without alpha channel
					$imgplain = imagecreatetruecolor($w, $h);
					imagecopy($imgplain, $im, 0, 0, 0, 0, $w, $h);
					// create temp image file
					$minfo = $this->_getImage($this->_tempimglnk, false);
					if (!$minfo) { return $this->_imageError($file, $firsttime, 'Error parsing temporary file image object created with GD library to parse PNG image'); }
					ob_start();
					$check = @imagepng($imgplain);
					if (!$check) { return $this->_imageError($file, $firsttime, 'Error creating temporary image object whilst using GD library to parse PNG image'); }
					$this->_tempimg = ob_get_contents();
					$this->_tempimglnk = 'var:_tempimg';
					ob_end_clean();
					$info = $this->_getImage($this->_tempimglnk, false);
					if (!$info) { return $this->_imageError($file, $firsttime, 'Error parsing temporary file image object created with GD library to parse PNG image'); }
					imagedestroy($imgplain);
					$imgmask = count($this->images)+1;
					$minfo['cs'] = 'DeviceGray';
					$minfo['i']=$imgmask ;
					$this->images[$tempfile_alpha] = $minfo;

				}
				else {
					$check = @imagepng($imgalpha, $tempfile_alpha);
					if (!$check) { return $this->_imageError($file, $firsttime, 'Failed to create temporary image file ('.$tempfile_alpha.') parsing PNG image with alpha channel ('.$errpng.')'); }
					imagedestroy($imgalpha);

					// extract image without alpha channel
					$imgplain = imagecreatetruecolor($w, $h);
					imagecopy($imgplain, $im, 0, 0, 0, 0, $w, $h);

					// create temp image file
					$check = @imagepng($imgplain, $tempfile);
					if (!$check) { return $this->_imageError($file, $firsttime, 'Failed to create temporary image file ('.$tempfile.') parsing PNG image with alpha channel ('.$errpng.')'); }
					imagedestroy($imgplain);
					// embed mask image
					$minfo = $this->_getImage($tempfile_alpha, false);
					unlink($tempfile_alpha);
					if (!$minfo) { return $this->_imageError($file, $firsttime, 'Error parsing temporary file ('.$tempfile_alpha.') created with GD library to parse PNG image'); }
					$imgmask = count($this->images)+1;
					$minfo['cs'] = 'DeviceGray';
					$minfo['i']=$imgmask ;
					$this->images[$tempfile_alpha] = $minfo;
					// embed image, masked with previously embedded mask
					$info = $this->_getImage($tempfile, false);
					unlink($tempfile);
					if (!$info) { return $this->_imageError($file, $firsttime, 'Error parsing temporary file ('.$tempfile.') created with GD library to parse PNG image'); }

				}
				$info['masked'] = $imgmask;
				if ($ppUx) { $info['set-dpi'] = $ppUx; }
				$info['type']='png';
				if ($firsttime) {
					$info['i']=count($this->images)+1;
					$this->images[$file]=$info;
				}
				return $info;
			   }
			   else { 	// No alpha/transparency set
				imagealphablending($im, false);
				imagesavealpha($im, false);
				imageinterlace($im, false);
				if (!is_writable($tempfile)) {
					ob_start();
					$check = @imagepng($im);
					if (!$check) { return $this->_imageError($file, $firsttime, 'Error creating temporary image object whilst using GD library to parse PNG image'); }
					$this->_tempimg = ob_get_contents();
					$this->_tempimglnk = 'var:_tempimg';
					ob_end_clean();
					$info = $this->_getImage($this->_tempimglnk, false);
					if (!$info) { return $this->_imageError($file, $firsttime, 'Error parsing temporary file image object created with GD library to parse PNG image'); }
					imagedestroy($im);
				}
				else {
					$check = @imagepng($im, $tempfile );
					if (!$check) { return $this->_imageError($file, $firsttime, 'Failed to create temporary image file ('.$tempfile.') parsing PNG image ('.$errpng.')'); }
					imagedestroy($im);
					$info = $this->_getImage($tempfile, false) ;
					unlink($tempfile );
					if (!$info) { return $this->_imageError($file, $firsttime, 'Error parsing temporary file ('.$tempfile.') created with GD library to parse PNG image'); }
				}
				if ($ppUx) { $info['set-dpi'] = $ppUx; }
				$info['type']='png';
				if ($firsttime) {
					$info['i']=count($this->images)+1;
					$this->images[$file]=$info;
				}
				return $info;
			   }
			}
		}

		else {
			$parms='/DecodeParms <</Predictor 15 /Colors '.($ct==2 ? 3 : 1).' /BitsPerComponent '.$bpc.' /Columns '.$w.'>>';
			//Scan chunks looking for palette, transparency and image data
			$pal='';
			$trns='';
			$pngdata='';
			$p = 33;
			do {
				$n=$this->_fourbytes2int(substr($data,$p,4));	$p += 4;
				$type=substr($data,$p,4);	$p += 4;
				if($type=='PLTE') {
					//Read palette
					$pal=substr($data,$p,$n);	$p += $n;
					$p += 4;
				}
				elseif($type=='tRNS') {
					//Read transparency info
					$t=substr($data,$p,$n);	$p += $n;
					if($ct==0) $trns=array(ord(substr($t,1,1)));
					elseif($ct==2) $trns=array(ord(substr($t,1,1)),ord(substr($t,3,1)),ord(substr($t,5,1)));
					else
					{
						$pos=strpos($t,chr(0));
						if(is_int($pos)) $trns=array($pos);
					}
					$p += 4;
				}
				elseif($type=='IDAT') {
					$pngdata.=substr($data,$p,$n);	$p += $n;
					$p += 4;
				}
				elseif($type=='IEND') { break; }
				else if (preg_match('/[a-zA-Z]{4}/',$type)) { $p += $n+4; }
				else { return $this->_imageError($file, $firsttime, 'Error parsing PNG image data'); }
			}
			while($n);
			if (!$pngdata) { return $this->_imageError($file, $firsttime, 'Error parsing PNG image data - no IDAT data found'); }
			if($colspace=='Indexed' and empty($pal)) { return $this->_imageError($file, $firsttime, 'Error parsing PNG image data - missing colour palette'); }
			$info = array('w'=>$w,'h'=>$h,'cs'=>$colspace,'bpc'=>$bpc,'f'=>'FlateDecode','parms'=>$parms,'pal'=>$pal,'trns'=>$trns,'data'=>$pngdata);
			$info['type']='png';
			if ($ppUx) { $info['set-dpi'] = $ppUx; }
		}

		if (!$info) { return $this->_imageError($file, $firsttime, 'Error parsing or converting PNG image'); }

		if ($firsttime) {
			$info['i']=count($this->images)+1;
			$this->images[$file]=$info;
		}
		return $info;
	}

	// GIF
	else if ($type == 'gif') {
	if (function_exists('gd_info')) { $gd = gd_info(); }
		else {$gd = array(); }
		if (isset($gd['GIF Read Support']) && $gd['GIF Read Support']) {
			$im = @imagecreatefromstring($data);
			if ($im) {
				$tempfile = _MPDF_TEMP_PATH.'_tempImgPNG'.RAND(1,10000).'.png';
				imagealphablending($im, false);
				imagesavealpha($im, false);
				imageinterlace($im, false);
				if (!is_writable($tempfile)) {
					ob_start();
					$check = @imagepng($im);
					if (!$check) { return $this->_imageError($file, $firsttime, 'Error creating temporary image object whilst using GD library to parse GIF image'); }
					$this->_tempimg = ob_get_contents();
					$this->_tempimglnk = 'var:_tempimg';
					ob_end_clean();
					$info = $this->_getImage($this->_tempimglnk, false);
					if (!$info) { return $this->_imageError($file, $firsttime, 'Error parsing temporary file image object created with GD library to parse GIF image'); }
					imagedestroy($im);
				}
				else {
					$check = @imagepng($im, $tempfile);
					if (!$check) { return $this->_imageError($file, $firsttime, 'Error creating temporary file ('.$tempfile.') whilst using GD library to parse GIF image'); }
					$info = $this->_getImage($tempfile, false);
					if (!$info) { return $this->_imageError($file, $firsttime, 'Error parsing temporary file ('.$tempfile.') created with GD library to parse GIF image'); }
					imagedestroy($im);
					unlink($tempfile);
				}
				$info['type']='gif';
				if ($firsttime) {
					$info['i']=count($this->images)+1;
					$this->images[$file]=$info;
				}
				return $info;
			}
			else { return $this->_imageError($file, $firsttime, 'Error creating GD image file from GIF image'); }
		}

		if (!class_exists('gif', false)) {
			include_once(_MPDF_PATH.'classes/gif.php');
		}
		$gif=new CGIF();

		$h=0;
		$w=0;
		$gif->loadFile($data, 0);

		if(isset($gif->m_img->m_gih->m_bLocalClr) && $gif->m_img->m_gih->m_bLocalClr) {
			$nColors = $gif->m_img->m_gih->m_nTableSize;
			$pal = $gif->m_img->m_gih->m_colorTable->toString();
			if($bgColor != -1) {
				$bgColor = $gif->m_img->m_gih->m_colorTable->colorIndex($bgColor);
			}
			$colspace='Indexed';
		} elseif(isset($gif->m_gfh->m_bGlobalClr) && $gif->m_gfh->m_bGlobalClr) {
			$nColors = $gif->m_gfh->m_nTableSize;
			$pal = $gif->m_gfh->m_colorTable->toString();
			if((isset($bgColor)) and $bgColor != -1) {
				$bgColor = $gif->m_gfh->m_colorTable->colorIndex($bgColor);
			}
			$colspace='Indexed';
		} else {
			$nColors = 0;
			$bgColor = -1;
			$colspace='DeviceGray';
			$pal='';
		}

		$trns='';
		if(isset($gif->m_img->m_bTrans) && $gif->m_img->m_bTrans && ($nColors > 0)) {
			$trns=array($gif->m_img->m_nTrans);
		}
		$gifdata=$gif->m_img->m_data;
		$w=$gif->m_gfh->m_nWidth;
		$h=$gif->m_gfh->m_nHeight;
		$gif->ClearData();

		if($colspace=='Indexed' and empty($pal)) {
			return $this->_imageError($file, $firsttime, 'Error parsing GIF image - missing colour palette');
		}
		if ($this->compress) {
			$gifdata=gzcompress($gifdata);
			$info = array( 'w'=>$w, 'h'=>$h, 'cs'=>$colspace, 'bpc'=>8, 'f'=>'FlateDecode', 'pal'=>$pal, 'trns'=>$trns, 'data'=>$gifdata);
		}
		else {
			$info = array( 'w'=>$w, 'h'=>$h, 'cs'=>$colspace, 'bpc'=>8, 'pal'=>$pal, 'trns'=>$trns, 'data'=>$gifdata);
		}
		$info['type']='gif';
		if ($firsttime) {
			$info['i']=count($this->images)+1;
			$this->images[$file]=$info;
		}
		return $info;
	}


	// UNKNOWN TYPE - try GD imagecreatefromstring
	else {
		if (function_exists('gd_info')) { $gd = gd_info(); }
		else {$gd = array(); }
		if (isset($gd['PNG Support']) && $gd['PNG Support']) {
			$im = @imagecreatefromstring($data);
			if (!$im) { return $this->_imageError($file, $firsttime, 'Error parsing image file - image type not recognised, and not supported by GD imagecreate'); }
			$tempfile = _MPDF_TEMP_PATH.'_tempImgPNG'.RAND(1,10000).'.png';
			imagealphablending($im, false);
			imagesavealpha($im, false);
			imageinterlace($im, false);
			$check = @imagepng($im, $tempfile);
			if (!$check) { return $this->_imageError($file, $firsttime, 'Error creating temporary file ('.$tempfile.') whilst using GD library to parse unknown image type'); }
			$info = $this->_getImage($tempfile, false);
			imagedestroy($im);
			unlink($tempfile);
			if (!$info) { return $this->_imageError($file, $firsttime, 'Error parsing temporary file ('.$tempfile.') created with GD library to parse unknown image type'); }
			$info['type']='png';
			if ($firsttime) {
				$info['i']=count($this->images)+1;
				$this->images[$file]=$info;
			}
			return $info;
		}
	}

	return $this->_imageError($file, $firsttime, 'Error parsing image file - image type not recognised');
}
//==============================================================
function _convImage(&$data, $colspace, $targetcs, $w, $h, $dpi, $mask) {
	if ($this->PDFA || $this->PDFX) { $mask=false; }
	$im = @imagecreatefromstring($data);
	$info = array();
	if ($im) {
		$imgdata = '';
		$mimgdata = '';
		$minfo = array();
		//Read transparency info
		$trns=array();
		$trnsrgb = false;
		if (!$this->PDFA && !$this->PDFX) {
		   $p = strpos($data,'tRNS');
		   if ($p) {
			$n=$this->_fourbytes2int(substr($data,($p-4),4));
			$t = substr($data,($p+4),$n);
			if ($colspace=='DeviceGray') {
				$trns=array(ord(substr($t,1,1)));
				$trnsrgb = array($trns[0],$trns[0],$trns[0]);
			}
			else if ($colspace=='DeviceRGB') {
				$trns=array(ord(substr($t,1,1)),ord(substr($t,3,1)),ord(substr($t,5,1)));
				$trnsrgb = $trns;
				if ($targetcs=='DeviceCMYK') {
					$col = $this->rgb2cmyk(array(3,$trns[0],$trns[1],$trns[2]));
					$c1 = intval($col[1]*2.55);
					$c2 = intval($col[2]*2.55);
					$c3 = intval($col[3]*2.55);
					$c4 = intval($col[4]*2.55);
					$trns = array($c1,$c2,$c3,$c4);
				}
				else if ($targetcs=='DeviceGray') {
					$c = intval(($trns[0] * .21) + ($trns[1] * .71) + ($trns[2] * .07));
					$trns = array($c);
				}
			}
			else {	// Indexed
				$pos = strpos($t,chr(0));
				if (is_int($pos)) {
					$pal = imagecolorsforindex($im, $pos);
					$r = $pal['red'];
					$g = $pal['green'];
					$b = $pal['blue'];
					$trns=array($r,$g,$b);	// ****
					$trnsrgb = $trns;
					if ($targetcs=='DeviceCMYK') {
						$col = $this->rgb2cmyk(array(3,$r,$g,$b));
						$c1 = intval($col[1]*2.55);
						$c2 = intval($col[2]*2.55);
						$c3 = intval($col[3]*2.55);
						$c4 = intval($col[4]*2.55);
						$trns = array($c1,$c2,$c3,$c4);
					}
					else if ($targetcs=='DeviceGray') {
						$c = intval(($r * .21) + ($g * .71) + ($b * .07));
						$trns = array($c);
					}
				}
			}
		   }
		}
		for ($i = 0; $i < $h; $i++) {
			for ($j = 0; $j < $w; $j++) {
				$rgb = imagecolorat($im, $j, $i);
				$r = ($rgb >> 16) & 0xFF;
				$g = ($rgb >> 8) & 0xFF;
				$b = $rgb & 0xFF;
				if ($colspace=='Indexed') {
					$pal = imagecolorsforindex($im, $rgb);
					$r = $pal['red'];
					$g = $pal['green'];
					$b = $pal['blue'];
				}

				if ($targetcs=='DeviceCMYK') {
					$col = $this->rgb2cmyk(array(3,$r,$g,$b));
					$c1 = intval($col[1]*2.55);
					$c2 = intval($col[2]*2.55);
					$c3 = intval($col[3]*2.55);
					$c4 = intval($col[4]*2.55);
					if ($trnsrgb) {
						// original pixel was not set as transparent but processed color does match
						if ($trnsrgb!=array($r,$g,$b) && $trns==array($c1,$c2,$c3,$c4)) {
							if ($c4==0) { $c4=1; } else { $c4--; }
						}
					}
					$imgdata .= chr($c1).chr($c2).chr($c3).chr($c4);
				}
				else if ($targetcs=='DeviceGray') {
					$c = intval(($r * .21) + ($g * .71) + ($b * .07));
					if ($trnsrgb) {
						// original pixel was not set as transparent but processed color does match
						if ($trnsrgb!=array($r,$g,$b) && $trns==array($c)) {
							if ($c==0) { $c=1; } else { $c--; }
						}
					}
					$imgdata .= chr($c);
				}
				else if ($targetcs=='DeviceRGB') {
					$imgdata .= chr($r).chr($g).chr($b);
				}
				if ($mask) {
					$col = imagecolorsforindex($im, $rgb);
					$gammacorr = 2.2;	// gamma correction
					$gamma = intval((pow((((127 - $col['alpha']) * 255 / 127) / 255), $gammacorr) * 255));
					$mimgdata .= chr($gamma);
				}
			}
		}

		if ($targetcs=='DeviceGray') { $ncols = 1; }
		else if ($targetcs=='DeviceRGB') { $ncols = 3; }
		else if ($targetcs=='DeviceCMYK') { $ncols = 4; }

		$imgdata = gzcompress($imgdata);
		$info = array('w'=>$w,'h'=>$h,'cs'=>$targetcs,'bpc'=>8,'f'=>'FlateDecode','data'=>$imgdata, 'type'=>'png',
			'parms'=>'/DecodeParms <</Colors '.$ncols.' /BitsPerComponent 8 /Columns '.$w.'>>');
		if ($dpi) { $info['set-dpi'] = $dpi; }
		if ($mask) {
			$mimgdata = gzcompress($mimgdata);
			$minfo = array('w'=>$w,'h'=>$h,'cs'=>'DeviceGray','bpc'=>8,'f'=>'FlateDecode','data'=>$mimgdata, 'type'=>'png',
			'parms'=>'/DecodeParms <</Colors '.$ncols.' /BitsPerComponent 8 /Columns '.$w.'>>');
			if ($dpi) { $minfo['set-dpi'] = $dpi; }
			$tempfile = '_tempImgPNG'.RAND(1,10000).'.png';
			$imgmask = count($this->images)+1;
			$minfo['i']=$imgmask ;
			$this->images[$tempfile] = $minfo;
			$info['masked'] = $imgmask;
		}
		else if ($trns) { $info['trns'] = $trns; }
		imagedestroy($im);
	}
	return $info;
}




function _fourbytes2int($s) {
	//Read a 4-byte integer from string
	return (ord($s[0])<<24) + (ord($s[1])<<16) + (ord($s[2])<<8) + ord($s[3]);
}

function _twobytes2int($s) {
	//Read a 2-byte integer from string
	return (ord(substr($s, 0, 1))<<8) + ord(substr($s, 1, 1));
}

function _jpgHeaderFromString(&$data) {
	$p = 4;
	$p += $this->_twobytes2int(substr($data, $p, 2));	// Length of initial marker block
	$marker = substr($data, $p, 2);
	while($marker != chr(255).chr(192) && $marker != chr(255).chr(194) && $p<strlen($data)) {
		// Start of frame marker (FFC0) or (FFC2) mPDF 4.4.004
		$p += ($this->_twobytes2int(substr($data, $p+2, 2))) + 2;	// Length of marker block
		$marker = substr($data, $p, 2);
	}
	if ($marker != chr(255).chr(192) && $marker != chr(255).chr(194)) { return false; }
	return substr($data, $p+2, 10);
}

function _jpgDataFromHeader($hdr) {
	$bpc = ord(substr($hdr, 2, 1));
	if (!$bpc) { $bpc = 8; }
	$h = $this->_twobytes2int(substr($hdr, 3, 2));
	$w = $this->_twobytes2int(substr($hdr, 5, 2));
	$channels = ord(substr($hdr, 7, 1));
	if ($channels==3) { $colspace='DeviceRGB'; }
	elseif($channels==4) { $colspace='DeviceCMYK'; }
	else { $colspace='DeviceGray'; }
	return array($w, $h, $colspace, $bpc);
}

function file_get_contents_by_curl($url, &$data) {
	$timeout = 5;
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_NOBODY, 0);
	curl_setopt ( $ch , CURLOPT_RETURNTRANSFER , 1 );
	curl_setopt ( $ch , CURLOPT_CONNECTTIMEOUT , $timeout );
	$data = curl_exec($ch);
	curl_close($ch);
}


function file_get_contents_by_socket($url, &$data) {
	$timeout = 1;
	$p = parse_url($url);
	$file = $p['path'];
	if ($p['query']) { $file .= '?'.$p['query']; }
	if(!($fh = @fsockopen($p['host'], 80, $errno, $errstr, $timeout))) { return false; }
	$getstring =
		"GET ".$file." HTTP/1.0 \r\n" .
		"Host: ".$p['host']." \r\n" .
		"Connection: close\r\n\r\n";
	fwrite($fh, $getstring);
	// Get rid of HTTP header
	$s = fgets($fh, 1024);
	if (!$s) { return false; }
	$httpheader .= $s;
	while (!feof($fh)) {
		$s = fgets($fh, 1024);
		if ( $s == "\r\n" ) { break; }
	}
	$data = '';
	while (!feof($fh)) {
		$data .= fgets($fh, 1024);
	}
	fclose($fh);
}

//==============================================================

function _imageTypeFromString(&$data) {
	$type = '';
	if (substr($data, 6, 4)== 'JFIF' || substr($data, 6, 4)== 'Exif') {
		$type = 'jpeg';
	}
	else if (substr($data, 0, 6)== "GIF87a" || substr($data, 0, 6)== "GIF89a") {
		$type = 'gif';
	}
	else if (substr($data, 0, 8)== chr(137).'PNG'.chr(13).chr(10).chr(26).chr(10)) {
		$type = 'png';
	}
	else if (preg_match('/<svg.*<\/svg>/is',$data)) {
		$type = 'svg';
	}
	// BMP images
	else if (substr($data, 0, 2)== "BM") {
		$type = 'bmp';
	}
	return $type;
}
//==============================================================

// Moved outside WMF as also needed for SVG
function _putformobjects() {
	reset($this->formobjects);
	while(list($file,$info)=each($this->formobjects)) {
		$this->_newobj();
		$this->formobjects[$file]['n']=$this->n;
		$this->_out('<</Type /XObject');
		$this->_out('/Subtype /Form');
		$this->_out('/Group '.($this->n+1).' 0 R');
		$this->_out('/BBox ['.$info['x'].' '.$info['y'].' '.($info['w']+$info['x']).' '.($info['h']+$info['y']).']');
		if ($this->compress)
			$this->_out('/Filter /FlateDecode');
		$data=($this->compress) ? gzcompress($info['data']) : $info['data'];
		$this->_out('/Length '.strlen($data).'>>');
		$this->_putstream($data);
		unset($this->formobjects[$file]['data']);
		$this->_out('endobj');
		// Required for SVG transparency (opacity) to work
		$this->_newobj();
		$this->_out('<</Type /Group');
		$this->_out('/S /Transparency');
		$this->_out('>>');
		$this->_out('endobj');
	}
}

function _freadint($f)
{
	//Read a 4-byte integer from file
	$i=ord(fread($f,1))<<24;
	$i+=ord(fread($f,1))<<16;
	$i+=ord(fread($f,1))<<8;
	$i+=ord(fread($f,1));
	return $i;
}

function _UTF16BEtextstring($s) {
	$s = $this->UTF8ToUTF16BE($s, true);
	return '('. $this->_escape($s).')';
}

function _textstring($s) {
	return '('. $this->_escape($s).')';
}


function _escape($s)
{
	// the chr(13) substitution fixes the Bugs item #1421290.
	return strtr($s, array(')' => '\\)', '(' => '\\(', '\\' => '\\\\', chr(13) => '\r'));
}

function _putstream($s) {
	$this->_out('stream');
	$this->_out($s);
	$this->_out('endstream');
}


function _out($s,$ln=true) {
	if($this->state==2) {
	   if ($this->bufferoutput) {
		$this->headerbuffer.= $s."\n";
	   }
	   else if ($this->table_rotate && !$this->processingHeader && !$this->processingFooter) {
		// Captures eveything in buffer for rotated tables;
		$this->tablebuffer .= $s . "\n";
	   }
	   else if ($this->kwt && !$this->processingHeader && !$this->processingFooter) {
		// Captures eveything in buffer for keep-with-table (h1-6);
		$this->kwt_buffer[] = array(
		's' => $s,							// Text string to output
		'x' => $this->x, 						// x when printed
		'y' => $this->y,					 	// y when printed
		);
	   }
	   else if (($this->keep_block_together) && !$this->processingHeader && !$this->processingFooter) {
		if (!isset($this->ktBlock[$this->page]['bottom_margin'])) {
			$this->ktBlock[$this->page]['bottom_margin'] = $this->y;
		}

		// Captures eveything in buffer;
		if (preg_match('/q \d+\.\d\d+ 0 0 (\d+\.\d\d+) \d+\.\d\d+ \d+\.\d\d+ cm \/(I|FO)\d+ Do Q/',$s,$m)) {	// Image data
			$h = ($m[1]/_MPDFK);
			// Update/overwrite the lowest bottom of printing y value for Keep together block
			$this->ktBlock[$this->page]['bottom_margin'] = $this->y+$h;
		}
		else { 	// Td Text Set in Cell()
			if (isset($this->ktBlock[$this->page]['bottom_margin'])) { $h = $this->ktBlock[$this->page]['bottom_margin'] - $this->y; }
			else { $h = 0; }
		}
		if ($h < 0) { $h = -$h; }
		$this->divbuffer[] = array(
		'page' => $this->page,
		's' => $s,							// Text string to output
		'x' => $this->x, 						// x when printed
		'y' => $this->y,					 	// y when printed (after column break)
		'h' => $h						 	// actual y at bottom when printed = y+h
		);
	   }
	   else {
		$this->pages[$this->page] .= $s.($ln == true ? "\n" : '');
	   }

	}
	else {
		$this->buffer .= $s.($ln == true ? "\n" : '');
	}
}



function Rotate($angle,$x=-1,$y=-1)
{
	if($x==-1)
		$x=$this->x;
	if($y==-1)
		$y=$this->y;
	if($this->angle!=0)
		$this->_out('Q');
	$this->angle=$angle;
	if($angle!=0)
	{
		$angle*=M_PI/180;
		$c=cos($angle);
		$s=sin($angle);
		$cx=$x*_MPDFK;
		$cy=($this->h-$y)*_MPDFK;
		$this->_out(sprintf('q %.5F %.5F %.5F %.5F %.3F %.3F cm 1 0 0 1 %.3F %.3F cm',$c,$s,-$s,$c,$cx,$cy,-$cx,-$cy));
	}
}



function CircularText($x, $y, $r, $text, $align='top', $fontfamily='', $fontsize=0, $fontstyle='', $kerning=120, $fontwidth=100, $divider) {	// mPDF 5.5.23
	if (!class_exists('directw', false)) { include(_MPDF_PATH.'classes/directw.php'); }
	if (empty($this->directw)) { $this->directw = new directw($this); }
	$this->directw->CircularText($x, $y, $r, $text, $align, $fontfamily, $fontsize, $fontstyle, $kerning, $fontwidth, $divider);	// mPDF 5.5.23
}


// From Invoice
function RoundedRect($x, $y, $w, $h, $r, $style = '')
{
	$hp = $this->h;
	if($style=='F')
		$op='f';
	elseif($style=='FD' or $style=='DF')
		$op='B';
	else
		$op='S';
	$MyArc = 4/3 * (sqrt(2) - 1);
	$this->_out(sprintf('%.3F %.3F m',($x+$r)*_MPDFK,($hp-$y)*_MPDFK ));
	$xc = $x+$w-$r ;
	$yc = $y+$r;
	$this->_out(sprintf('%.3F %.3F l', $xc*_MPDFK,($hp-$y)*_MPDFK ));

	$this->_Arc($xc + $r*$MyArc, $yc - $r, $xc + $r, $yc - $r*$MyArc, $xc + $r, $yc);
	$xc = $x+$w-$r ;
	$yc = $y+$h-$r;
	$this->_out(sprintf('%.3F %.3F l',($x+$w)*_MPDFK,($hp-$yc)*_MPDFK));
	$this->_Arc($xc + $r, $yc + $r*$MyArc, $xc + $r*$MyArc, $yc + $r, $xc, $yc + $r);
	$xc = $x+$r ;
	$yc = $y+$h-$r;
	$this->_out(sprintf('%.3F %.3F l',$xc*_MPDFK,($hp-($y+$h))*_MPDFK));
	$this->_Arc($xc - $r*$MyArc, $yc + $r, $xc - $r, $yc + $r*$MyArc, $xc - $r, $yc);
	$xc = $x+$r ;
	$yc = $y+$r;
	$this->_out(sprintf('%.3F %.3F l',($x)*_MPDFK,($hp-$yc)*_MPDFK ));
	$this->_Arc($xc - $r, $yc - $r*$MyArc, $xc - $r*$MyArc, $yc - $r, $xc, $yc - $r);
	$this->_out($op);
}

function _Arc($x1, $y1, $x2, $y2, $x3, $y3)
{
	$h = $this->h;
	$this->_out(sprintf('%.3F %.3F %.3F %.3F %.3F %.3F c ', $x1*_MPDFK, ($h-$y1)*_MPDFK,
						$x2*_MPDFK, ($h-$y2)*_MPDFK, $x3*_MPDFK, ($h-$y3)*_MPDFK));
}




//====================================================





function UTF8StringToArray($str, $addSubset=true) {
   $out = array();
   $len = strlen($str);
   for ($i = 0; $i < $len; $i++) {
	$uni = -1;
      $h = ord($str[$i]);
      if ( $h <= 0x7F )
         $uni = $h;
      elseif ( $h >= 0xC2 ) {
         if ( ($h <= 0xDF) && ($i < $len -1) )
            $uni = ($h & 0x1F) << 6 | (ord($str[++$i]) & 0x3F);
         elseif ( ($h <= 0xEF) && ($i < $len -2) )
            $uni = ($h & 0x0F) << 12 | (ord($str[++$i]) & 0x3F) << 6 | (ord($str[++$i]) & 0x3F);
         elseif ( ($h <= 0xF4) && ($i < $len -3) )
            $uni = ($h & 0x0F) << 18 | (ord($str[++$i]) & 0x3F) << 12 | (ord($str[++$i]) & 0x3F) << 6 | (ord($str[++$i]) & 0x3F);
      }
	if ($uni >= 0) {
		$out[] = $uni;
		if ($addSubset && isset($this->CurrentFont['subset'])) {
			$this->CurrentFont['subset'][$uni] = $uni;
		}
	}
   }
   return $out;
}


//Convert utf-8 string to <HHHHHH> for Font Subsets
function UTF8toSubset($str) {
	$ret = '<';
	$str = preg_replace('/'.preg_quote($this->aliasNbPg,'/').'/', chr(7), $str );
	$str = preg_replace('/'.preg_quote($this->aliasNbPgGp,'/').'/', chr(8), $str );
	$unicode = $this->UTF8StringToArray($str);
	$orig_fid = $this->CurrentFont['subsetfontids'][0];
	$last_fid = $this->CurrentFont['subsetfontids'][0];
	foreach($unicode as $c) {
	   if ($c == 7 || $c == 8) {
			if ($orig_fid != $last_fid) {
				$ret .= '> Tj /F'.$orig_fid.' '.$this->FontSizePt.' Tf <';
				$last_fid = $orig_fid;
			}
			if ($c == 7) { $ret .= $this->aliasNbPgHex; }
			else { $ret .= $this->aliasNbPgGpHex; }
			continue;
	   }
	   for ($i=0; $i<99; $i++) {
		// return c as decimal char
		$init = array_search($c, $this->CurrentFont['subsets'][$i]);
		if ($init!==false) {
			if ($this->CurrentFont['subsetfontids'][$i] != $last_fid) {
				$ret .= '> Tj /F'.$this->CurrentFont['subsetfontids'][$i].' '.$this->FontSizePt.' Tf <';
				$last_fid = $this->CurrentFont['subsetfontids'][$i];
			}
			$ret .= sprintf("%02s", strtoupper(dechex($init)));
			break;
		}
		// TrueType embedded SUBSETS
		else if (count($this->CurrentFont['subsets'][$i]) < 255) {
			$n = count($this->CurrentFont['subsets'][$i]);
			$this->CurrentFont['subsets'][$i][$n] = $c;
			if ($this->CurrentFont['subsetfontids'][$i] != $last_fid) {
				$ret .= '> Tj /F'.$this->CurrentFont['subsetfontids'][$i].' '.$this->FontSizePt.' Tf <';
				$last_fid = $this->CurrentFont['subsetfontids'][$i];
			}
			$ret .= sprintf("%02s", strtoupper(dechex($n)));
			break;
		}
		else if (!isset($this->CurrentFont['subsets'][($i+1)])) {
			// TrueType embedded SUBSETS
			$this->CurrentFont['subsets'][($i+1)] = array(0=>0);
			$new_fid = count($this->fonts)+$this->extraFontSubsets+1;
			$this->CurrentFont['subsetfontids'][($i+1)] = $new_fid;
			$this->extraFontSubsets++;
		}
	   }
	}
	$ret .= '>';
	if ($last_fid != $orig_fid) {
		$ret .= ' Tj /F'.$orig_fid.' '.$this->FontSizePt.' Tf <> ';
	}
	return $ret;
}


// Converts UTF-8 strings to UTF16-BE.
function UTF8ToUTF16BE($str, $setbom=true) {
	if ($this->checkSIP && preg_match("/([\x{20000}-\x{2FFFF}])/u", $str)) {
	   if (!in_array($this->currentfontfamily, array('gb','big5','sjis','uhc','gbB','big5B','sjisB','uhcB','gbI','big5I','sjisI','uhcI',
		'gbBI','big5BI','sjisBI','uhcBI'))) {
		$str = preg_replace("/[\x{20000}-\x{2FFFF}]/u", chr(0), $str);
	   }
	}
	if ($this->checkSMP && preg_match("/([\x{10000}-\x{1FFFF}])/u", $str )) {
		$str = preg_replace("/[\x{10000}-\x{1FFFF}]/u", chr(0), $str );
	}
	$outstr = ""; // string to be returned
	if ($setbom) {
		$outstr .= "\xFE\xFF"; // Byte Order Mark (BOM)
	}
	$outstr .= mb_convert_encoding($str, 'UTF-16BE', 'UTF-8');
	return $outstr;
}





// ====================================================
// ====================================================

//////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////
function SetAutoFont($af = AUTOFONT_ALL) {
	if ($this->onlyCoreFonts) { return false; }
	if (!$af && $af !== 0) { $af = AUTOFONT_ALL; }
	$this->autoFontGroups = $af;
	if ($this->autoFontGroups ) {
		$this->useLang = true;
	}
}


function SetDefaultFont($font) {
	// Disallow embedded fonts to be used as defaults in PDFA
	if ($this->PDFA || $this->PDFX) {
		if (strtolower($font) == 'ctimes') { $font = 'serif'; }
		if (strtolower($font) == 'ccourier') { $font = 'monospace'; }
		if (strtolower($font) == 'chelvetica') { $font = 'sans-serif'; }
	}
  	$font = $this->SetFont($font);	// returns substituted font if necessary
	$this->default_font = $font;
	$this->original_default_font = $font;
	$this->defaultCSS['BODY']['FONT-FAMILY'] = $font;
	$this->CSS['BODY']['FONT-FAMILY'] = $font;
}

function SetDefaultFontSize($fontsize) {
	$this->default_font_size = $fontsize;
	$this->original_default_font_size = $fontsize;
	$this->SetFontSize($fontsize);
	$this->defaultCSS['BODY']['FONT-SIZE'] = $fontsize . 'pt';
	$this->CSS['BODY']['FONT-SIZE'] = $fontsize . 'pt';
}

function SetDefaultBodyCSS($prop, $val) {
   if ($prop) {
	$this->defaultCSS['BODY'][strtoupper($prop)] = $val;
	$this->CSS['BODY'][strtoupper($prop)] = $val;
  }
}


function SetDirectionality($dir='ltr') {
		$this->directionality = 'ltr';
		$this->defaultAlign = 'L';
		$this->defaultTableAlign = 'L';
	$this->CSS['BODY']['DIRECTION'] = $this->directionality;
}



// Added to set line-height-correction
function SetLineHeightCorrection($val) {
	if ($val > 0) { $this->default_lineheight_correction = $val; }
	else { $this->default_lineheight_correction = 1.2; }
}

// Set a (fixed) lineheight to an actual value - either to named fontsize(pts) or default
function SetLineHeight($FontPt='',$spacing = '') {
   if ($this->shrin_k > 1) { $k = $this->shrin_k; }
   else { $k = 1; }
   if ($spacing > 0) {
	if (preg_match('/mm/',$spacing)) {
		$this->lineheight = ($spacing + 0.0) / $k; // convert to number
	}
	else  {
		if ($FontPt) { $this->lineheight = (($FontPt/_MPDFK) *$spacing); }
		else { $this->lineheight = (($this->FontSizePt/_MPDFK) *$spacing); }
	}
   }
   else {
	if ($FontPt) { $this->lineheight = (($FontPt/_MPDFK) *$this->normalLineheight); }
	else { $this->lineheight = (($this->FontSizePt/_MPDFK) *$this->normalLineheight); }
   }
}

function _computeLineheight($lh, $fs='') {
	if ($this->shrin_k > 1) { $k = $this->shrin_k; }
	else { $k = 1; }
	if (!$fs) { $fs = $this->FontSize; }
	if (preg_match('/mm/',$lh)) {
		return (($lh + 0.0) / $k); // convert to number
	}
	else if ($lh > 0) {
		return ($fs * $lh);
	}
	else if (isset($this->normalLineheight)) { return ($fs * $this->normalLineheight); }
	else return ($fs * $this->default_lineheight_correction);
}


function SetBasePath($str='') {
  if ( isset($_SERVER['HTTP_HOST']) ) { $host = $_SERVER['HTTP_HOST']; }
  else if ( isset($_SERVER['SERVER_NAME']) ) { $host = $_SERVER['SERVER_NAME']; }
  else { $host = ''; }
  if (!$str) {
	if ($_SERVER['SCRIPT_NAME']) { $currentPath = dirname($_SERVER['SCRIPT_NAME']); }
	else { $currentPath = dirname($_SERVER['PHP_SELF']); }
	$currentPath = str_replace("\\","/",$currentPath);
	if ($currentPath == '/') { $currentPath = ''; }
	if ($host) { $currpath = 'http://' . $host . $currentPath .'/'; }
	else { $currpath = ''; }
	$this->basepath = $currpath;
	$this->basepathIsLocal = true;
	return;
  }
  $str = preg_replace('/\?.*/','',$str);
  if (!preg_match('/(http|https|ftp):\/\/.*\//i',$str)) { $str .= '/'; }
  $str .= 'xxx';	// in case $str ends in / e.g. http://www.bbc.co.uk/
  $this->basepath = dirname($str) . "/";	// returns e.g. e.g. http://www.google.com/dir1/dir2/dir3/
  $this->basepath = str_replace("\\","/",$this->basepath); //If on Windows
  $tr = parse_url($this->basepath);
  if (isset($tr['host']) && ($tr['host'] == $host)) { $this->basepathIsLocal = true; }
  else { $this->basepathIsLocal = false; }
}


function GetFullPath(&$path,$basepath='') {
	// When parsing CSS need to pass temporary basepath - so links are relative to current stylesheet
	if (!$basepath) { $basepath = $this->basepath; }
	//Fix path value
	$path = str_replace("\\","/",$path); //If on Windows
	//Get link info and obtain its absolute path
	$regexp = '|^./|';	// Inadvertently corrects "./path/etc" and "//www.domain.com/etc"
	$path = preg_replace($regexp,'',$path);

	if(substr($path,0,1) == '#') { return; }
	if (stristr($path,"mailto:") !== false) { return; }
	if (strpos($path,"../") !== false ) { //It is a Relative Link
		$backtrackamount = substr_count($path,"../");
		$maxbacktrack = substr_count($basepath,"/") - 1;
		$filepath = str_replace("../",'',$path);
		$path = $basepath;
		//If it is an invalid relative link, then make it go to directory root
		if ($backtrackamount > $maxbacktrack) $backtrackamount = $maxbacktrack;
		//Backtrack some directories
		for( $i = 0 ; $i < $backtrackamount + 1 ; $i++ ) $path = substr( $path, 0 , strrpos($path,"/") );
		$path = $path . "/" . $filepath; //Make it an absolute path
	}
	else if( strpos($path,":/") === false || strpos($path,":/") > 10) { //It is a Local Link
		if (substr($path,0,1) == "/") {
			$tr = parse_url($basepath);
			$root = $tr['scheme'].'://'.$tr['host'];
			$path = $root . $path;
		}
		else { $path = $basepath . $path; }
	}
	//Do nothing if it is an Absolute Link
}


// Used for external CSS files
function _get_file($path) {
	// If local file try using local path (? quicker, but also allowed even if allow_url_fopen false)
	$contents = '';
	$contents = @file_get_contents($path);
	if ($contents) { return $contents; }
	if ($this->basepathIsLocal) {
		$tr = parse_url($path);
		$lp=getenv("SCRIPT_NAME");
		$ap=realpath($lp);
		$ap=str_replace("\\","/",$ap);
		$docroot=substr($ap,0,strpos($ap,$lp));
		// WriteHTML parses all paths to full URLs; may be local file name
		if ($tr['scheme'] && $tr['host'] && $_SERVER["DOCUMENT_ROOT"] ) {
			$localpath = $_SERVER["DOCUMENT_ROOT"] . $tr['path'];
		}
		// DOCUMENT_ROOT is not returned on IIS
		else if ($docroot) {
			$localpath = $docroot . $tr['path'];
		}
		else { $localpath = $path; }
		$contents = @file_get_contents($localpath);
	}
	// if not use full URL
	else if (!$contents && !ini_get('allow_url_fopen') && function_exists("curl_init"))  {
		$ch = curl_init($path);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt ( $ch , CURLOPT_RETURNTRANSFER , 1 );
		$contents = curl_exec($ch);
		curl_close($ch);
	}
	return $contents;
}


function docPageNum($num = 0, $extras = false) {
	if ($num < 1) { $num = $this->page; }
	$type = '1';	// set default decimal
	$ppgno = $num;
	$suppress = 0;
	$offset = 0;

	$lastreset = 0;
	foreach($this->PageNumSubstitutions AS $psarr) {
		if ($num >= $psarr['from']) {
			if ($psarr['reset']) {
				if ($psarr['reset']>1) { $offset = $psarr['reset']-1; }
				$ppgno = $num - $psarr['from'] + 1 + $offset;
				$lastreset = $psarr['from'];
			}
			if ($psarr['type']) { $type = $psarr['type']; }
			if (strtoupper($psarr['suppress'])=='ON' || $psarr['suppress']==1) { $suppress = 1; }
			else if (strtoupper($psarr['suppress'])=='OFF') { $suppress = 0; }
		}
	}
	if ($suppress) { return ''; }

	foreach($this->pgsIns AS $k=>$v) {
		if ($k>$lastreset && $k<$num) {
			$ppgno -= $v;
		}
	}
	if ($type=='A') { $ppgno = $this->dec2alpha($ppgno,true); }
	else if ($type=='a') { $ppgno = $this->dec2alpha($ppgno,false);}
	else if ($type=='I') { $ppgno = $this->dec2roman($ppgno,true); }
	else if ($type=='i') { $ppgno = $this->dec2roman($ppgno,false); }
	if ($extras) { $ppgno = $this->pagenumPrefix . $ppgno . $this->pagenumSuffix; }
	return $ppgno;
}


function docPageSettings($num = 0) {
	// Returns current type (numberstyle), suppression state for this page number;
	// reset is only returned if set for this page number
	if ($num < 1) { $num = $this->page; }
	$type = '1';	// set default decimal
	$ppgno = $num;
	$suppress = 0;
	$offset = 0;
	$reset = '';
	foreach($this->PageNumSubstitutions AS $psarr) {
		if ($num >= $psarr['from']) {
			if ($psarr['reset']) {
				if ($psarr['reset']>1) { $offset = $psarr['reset']-1; }
				$ppgno = $num - $psarr['from'] + 1 + $offset;
			}
			if ($psarr['type']) { $type = $psarr['type']; }
			if (strtoupper($psarr['suppress'])=='ON' || $psarr['suppress']==1) { $suppress = 1; }
			else if (strtoupper($psarr['suppress'])=='OFF') { $suppress = 0; }
		}
		if ($num == $psarr['from']) { $reset = $psarr['reset']; }
	}
	if ($suppress) { $suppress = 'on'; }
	else { $suppress = 'off'; }
	return array($type, $suppress, $reset);
}

function docPageNumTotal($num = 0, $extras = false) {
	if ($num < 1) { $num = $this->page; }
	$type = '1';	// set default decimal
	$ppgstart = 1;
	$ppgend = count($this->pages)+1;
	$suppress = 0;
	$offset = 0;
	foreach($this->PageNumSubstitutions AS $psarr) {
		if ($num >= $psarr['from']) {
			if ($psarr['reset']) {
				if ($psarr['reset']>1) { $offset = $psarr['reset']-1; }
				$ppgstart = $psarr['from'] + $offset;
				$ppgend = count($this->pages)+1 + $offset;
			}
			if ($psarr['type']) { $type = $psarr['type']; }
			if (strtoupper($psarr['suppress'])=='ON' || $psarr['suppress']==1) { $suppress = 1; }
			else if (strtoupper($psarr['suppress'])=='OFF') { $suppress = 0; }
		}
		if ($num < $psarr['from']) {
			if ($psarr['reset']) {
				$ppgend = $psarr['from'] + $offset;
				break;
			}
		}
	}
	if ($suppress) { return ''; }
	$ppgno = $ppgend-$ppgstart+$offset;
	if ($extras) { $ppgno = $this->nbpgPrefix . $ppgno . $this->nbpgSuffix; }
	return $ppgno;
}

function RestartDocTemplate() {
	$this->docTemplateStart = $this->page;
}



//Page header
function Header($content='') {

	$this->cMarginL = 0;
	$this->cMarginR = 0;


  $this->processingHeader=true;
  $h = $this->headerDetails;
  if(count($h)) {

	if ($this->forcePortraitHeaders && $this->CurOrientation=='L' && $this->CurOrientation!=$this->DefOrientation) {
		$this->_out(sprintf('q 0 -1 1 0 0 %.3F cm ',($this->h*_MPDFK)));
		$yadj = $this->w - $this->h;
		$headerpgwidth = $this->h - $this->orig_lMargin - $this->orig_rMargin;
		if (($this->mirrorMargins) && (($this->page)%2==0)) {	// EVEN
			$headerlmargin = $this->orig_rMargin;
		}
		else {
			$headerlmargin = $this->orig_lMargin;
		}
	}
	else {
		$yadj = 0;
		$headerpgwidth = $this->pgwidth;
		$headerlmargin = $this->lMargin;
	}

	$this->y = $this->margin_header - $yadj ;
	$this->SetTColor($this->ConvertColor(0));
    	$this->SUP = false;
	$this->SUB = false;
	$this->bullet = false;

	// only show pagenumber if numbering on
	$pgno = $this->docPageNum($this->page, true);

	if (($this->mirrorMargins) && (($this->page)%2==0)) {	// EVEN
			$side = 'even';
	}
	else {	// ODD	// OR NOT MIRRORING MARGINS/FOOTERS = DEFAULT
			$side = 'odd';
	}
	$maxfontheight = 0;
	foreach(array('L','C','R') AS $pos) {
	  if (isset($h[$side][$pos]['content']) && $h[$side][$pos]['content']) {
		if (isset($h[$side][$pos]['font-size']) && $h[$side][$pos]['font-size']) { $hfsz = $h[$side][$pos]['font-size']; }
		else { $hfsz = $this->default_font_size; }
		$maxfontheight = max($maxfontheight,$hfsz);
	  }
	}
	// LEFT-CENTER-RIGHT
	foreach(array('L','C','R') AS $pos) {
	  if (isset($h[$side][$pos]['content']) && $h[$side][$pos]['content']) {
		$hd = str_replace('{PAGENO}',$pgno,$h[$side][$pos]['content']);
		$hd = str_replace($this->aliasNbPgGp,$this->nbpgPrefix.$this->aliasNbPgGp.$this->nbpgSuffix,$hd);
		$hd = preg_replace('/\{DATE\s+(.*?)\}/e',"date('\\1')",$hd);
		if (isset($h[$side][$pos]['font-family']) && $h[$side][$pos]['font-family']) { $hff = $h[$side][$pos]['font-family']; }
		else { $hff = $this->original_default_font; }
		if (isset($h[$side][$pos]['font-size']) && $h[$side][$pos]['font-size']) { $hfsz = $h[$side][$pos]['font-size']; }
		else { $hfsz = $this->original_default_font_size; }	// pts
		$maxfontheight = max($maxfontheight,$hfsz);
		$hfst = '';
		if (isset($h[$side][$pos]['font-style']) && $h[$side][$pos]['font-style']) {
			$hfst = $h[$side][$pos]['font-style'];
		}
		if (isset($h[$side][$pos]['color']) && $h[$side][$pos]['color']) {
			$hfcol = $h[$side][$pos]['color'];
			$cor = $this->ConvertColor($hfcol);
			if ($cor) { $this->SetTColor($cor); }
		}
		else { $hfcol = ''; }
		$this->SetFont($hff,$hfst,$hfsz,true,true);
		$this->x = $headerlmargin ;
		$this->y = $this->margin_header - $yadj ;

		$hd = $this->purify_utf8_text($hd);
		if ($this->text_input_as_HTML) {
			$hd = $this->all_entities_to_utf8($hd);
		}
		// CONVERT CODEPAGE
		if ($this->usingCoreFont) { $hd = mb_convert_encoding($hd,$this->mb_enc,'UTF-8'); }
		// DIRECTIONALITY RTL
		// Font-specific ligature substitution for Indic fonts
		$align = $pos;
		if ($pos!='L' && (strpos($hd,$this->aliasNbPg)!==false || strpos($hd,$this->aliasNbPgGp)!==false)) {
			if (strpos($hd,$this->aliasNbPgGp)!==false) { $type= 'nbpggp'; } else { $type= 'nbpg'; }
			$this->_out('{mpdfheader'.$type.' '.$pos.' ff='.$hff.' fs='.$hfst.' fz='.$hfsz.'}');
			$this->Cell($headerpgwidth ,$maxfontheight/_MPDFK ,$hd,0,0,$align,0,'',0,0,0,'M');
			$this->_out('Q');
		}
		else {
			$this->Cell($headerpgwidth ,$maxfontheight/_MPDFK ,$hd,0,0,$align,0,'',0,0,0,'M');
		}
		if ($hfcol) { $this->SetTColor($this->ConvertColor(0)); }
	  }
	}
	//Return Font to normal
	$this->SetFont($this->default_font,'',$this->original_default_font_size);
	// LINE
	if (isset($h[$side]['line']) && $h[$side]['line']) {
		$this->SetLineWidth(0.1);
		$this->SetDColor($this->ConvertColor(0));
		$this->Line($headerlmargin , $this->margin_header + ($maxfontheight*(1+$this->header_line_spacing)/_MPDFK) - $yadj , $headerlmargin + $headerpgwidth, $this->margin_header + ($maxfontheight*(1+$this->header_line_spacing)/_MPDFK) - $yadj  );
	}
	if ($this->forcePortraitHeaders && $this->CurOrientation=='L' && $this->CurOrientation!=$this->DefOrientation) {
		$this->_out('Q');
	}
  }
  $this->SetY($this->tMargin);

  $this->processingHeader=false;
}



function TableHeaderFooter($content='',$tablestartpage='',$tablestartcolumn ='',$horf = 'H',$level, $firstSpread=true, $finalSpread=true) {
  if(($horf=='H' || $horf=='F') && !empty($content)) {
	$table = &$this->table[1][1];
	// Advance down page by half width of top border

	if ($horf=='H') { // Only if header
		if ($table['borders_separate']) { $adv = $table['border_spacing_V']/2 + $table['border_details']['T']['w'] + $table['padding']['T'];  }
		else { $adv = $table['max_cell_border_width']['T'] /2 ; }
		if ($adv) {
		   if ($this->table_rotate) {
			$this->y += ($adv);
		   }
		   else {
			$this->DivLn($adv,$this->blklvl,true);
		   }
		}
	}

   if ($horf=='F') { // Table Footer
	$firstrow = count($table['cells']) - $table['footernrows'];
	$lastrow = count($table['cells']) - 1;
   }
   else { 	// Table Header
	$firstrow = 0;
	$lastrow = $table['headernrows'] - 1;
   }

   $topy = $content[$firstrow][0]['y']-$this->y;

   for ($i=$firstrow ; $i<=$lastrow; $i++) {

    $y = $this->y;


    $colctr = 0;
    foreach($content[$i] as $tablehf) {
	$colctr++;
	$y = $tablehf['y'] - $topy;
      $this->y = $y;
      //Set some cell values
      $x = $tablehf['x'];
	if (($this->mirrorMargins) && ($tablestartpage == 'ODD') && (($this->page)%2==0)) {	// EVEN
		$x = $x +$this->MarginCorrection;
	}
	else if (($this->mirrorMargins) && ($tablestartpage == 'EVEN') && (($this->page)%2==1)) {	// ODD
		$x = $x +$this->MarginCorrection;
	}

	if ($colctr==1) { $x0 = $x; }

	// mPDF ITERATION
	if ($this->iterationCounter) {
	   foreach($tablehf['textbuffer'] AS $k=>$t) {
		if (!is_array($t[0]) && preg_match('/{iteration ([a-zA-Z0-9_]+)}/',$t[0], $m)) {	// mPDF 5.5.06
			$vname = '__'.$m[1].'_';
			if (!isset($this->$vname)) { $this->$vname = 1; }
			else { $this->$vname++; }
			$tablehf['textbuffer'][$k][0] = preg_replace('/{iteration '.$m[1].'}/', $this->$vname, $tablehf['textbuffer'][$k][0]);
		}
	   }
	}


      $w = $tablehf['w'];
      $h = $tablehf['h'];
      $va = $tablehf['va'];
      $R = $tablehf['R'];
      $mih = $tablehf['mih'];
      $border = $tablehf['border'];
      $border_details = $tablehf['border_details'];
      $padding = $tablehf['padding'];
	$this->tabletheadjustfinished = true;

      $textbuffer = $tablehf['textbuffer'];

      $align = $tablehf['a'];
      //Align
      $this->divalign=$align;
	$this->x = $x;

	if ($this->ColActive) {
		if ($table['borders_separate']) {
		 $tablefill = isset($table['bgcolor'][-1]) ? $table['bgcolor'][-1] : 0;
		 if ($tablefill) {
  				$color = $this->ConvertColor($tablefill);
  				if ($color) {
					$xadj = ($table['border_spacing_H']/2);
					$yadj = ($table['border_spacing_V']/2);
					$wadj = $table['border_spacing_H'];
					$hadj = $table['border_spacing_V'];
 			   		if ($i == $firstrow && $horf=='H') {		// Top
						$yadj += $table['padding']['T'] + $table['border_details']['T']['w'] ;
						$hadj += $table['padding']['T'] + $table['border_details']['T']['w'] ;
			   		}
			   		if (($i == ($lastrow) || (isset($tablehf['rowspan']) && ($i+$tablehf['rowspan']) == ($lastrow+1))  || (!isset($tablehf['rowspan']) && ($i+1) == ($lastrow+1))) && $horf=='F') {	// Bottom
						$hadj += $table['padding']['B'] + $table['border_details']['B']['w'] ;
			   		}
			   		if ($colctr == 1) {		// Left
						$xadj += $table['padding']['L'] + $table['border_details']['L']['w'] ;
						$wadj += $table['padding']['L'] + $table['border_details']['L']['w'] ;
			   		}
			   		if ($colctr == count($content[$i]) ) {	// Right
						$wadj += $table['padding']['R'] + $table['border_details']['R']['w'] ;
			   		}
					$this->SetFColor($color);
					$this->Rect($x - $xadj, $y - $yadj, $w + $wadj, $h + $hadj, 'F');
				}
		 }
		}
	}

	if ($table['empty_cells']!='hide' || !empty($textbuffer) || !$table['borders_separate']) { $paintcell = true; }
	else { $paintcell = false; }

	//Vertical align
	if ($R && INTVAL($R) > 0 && isset($va) && $va!='B') { $va='B';}

	if (!isset($va) || empty($va) || $va=='M') $this->y += ($h-$mih)/2;
      elseif (isset($va) && $va=='B') $this->y += $h-$mih;


	//TABLE ROW OR CELL FILL BGCOLOR
	$fill = 0;
	if (isset($tablehf['bgcolor']) && $tablehf['bgcolor'] && $tablehf['bgcolor']!='transparent') {
		$fill = $tablehf['bgcolor'];
		$leveladj = 6;
	}
	else if (isset($content[$i][0]['trbgcolor']) && $content[$i][0]['trbgcolor'] && $content[$i][0]['trbgcolor']!='transparent') { // Row color
		$fill = $content[$i][0]['trbgcolor'];
		$leveladj = 3;
	}
	if ($fill && $paintcell) {
  		$color = $this->ConvertColor($fill);
  		if ($color) {
 			if ($table['borders_separate']) {
			   if ($this->ColActive) {
				$this->SetFColor($color);
				$this->Rect($x+ ($table['border_spacing_H']/2), $y+ ($table['border_spacing_V']/2), $w- $table['border_spacing_H'], $h- $table['border_spacing_V'], 'F');
			   }
			   else {
				$this->tableBackgrounds[$level*9+$leveladj][] = array('gradient'=>false, 'x'=>($x + ($table['border_spacing_H']/2)), 'y'=>($y + ($table['border_spacing_V']/2)), 'w'=>($w - $table['border_spacing_H']), 'h'=>($h - $table['border_spacing_V']), 'col'=>$color);
			   }
			}
 			else {
			   if ($this->ColActive) {
				$this->SetFColor($color);
				$this->Rect($x, $y, $w, $h, 'F');
			   }
			   else {
				$this->tableBackgrounds[$level*9+$leveladj][] = array('gradient'=>false, 'x'=>$x, 'y'=>$y, 'w'=>$w, 'h'=>$h, 'col'=>$color);
			   }
			}
		}
	}



   	//Cell Border
 	if ($table['borders_separate'] && $paintcell && $border) {
 		$this->_tableRect($x+ ($table['border_spacing_H']/2)+($border_details['L']['w'] /2), $y+ ($table['border_spacing_V']/2)+($border_details['T']['w'] /2), $w-$table['border_spacing_H']-($border_details['L']['w'] /2)-($border_details['R']['w'] /2), $h- $table['border_spacing_V']-($border_details['T']['w'] /2)-($border_details['B']['w']/2), $border, $border_details, false, $table['borders_separate']);
	}
 	else if ($paintcell && $border) {
		$this->_tableRect($x, $y, $w, $h, $border, $border_details, true, $table['borders_separate']);  	// true causes buffer
	}

 	//Print cell content
      //$this->divheight = $this->table_lineheight*$this->lineheight;
      if (!empty($textbuffer)) {
		if ($horf=='F' && preg_match('/{colsum([0-9]*)[_]*}/', $textbuffer[0][0], $m)) {
			$rep = sprintf("%01.".intval($m[1])."f", $this->colsums[$colctr-1]);
			$textbuffer[0][0] = preg_replace('/{colsum[0-9_]*}/', $rep ,$textbuffer[0][0]);
		}

		if ($R) {
					$cellPtSize = $textbuffer[0][11] / $this->shrin_k;
					if (!$cellPtSize) { $cellPtSize = $this->default_font_size; }
					$cellFontHeight = ($cellPtSize/_MPDFK);
					$opx = $this->x;
					$opy = $this->y;
					$angle = INTVAL($R);
					// Only allow 45 - 90 degrees (when bottom-aligned) or -90
					if ($angle > 90) { $angle = 90; }
					else if ($angle > 0 && (isset($va) && $va!='B')) { $angle = 90; }
					else if ($angle > 0 && $angle <45) { $angle = 45; }
					else if ($angle < 0) { $angle = -90; }
					$offset = ((sin(deg2rad($angle))) * 0.37 * $cellFontHeight);
					if (isset($align) && $align =='R') {
						$this->x += ($w) + ($offset) - ($cellFontHeight/3) - ($padding['R'] + $border_details['R']['w']);
					}
					else if (!isset($align ) || $align =='C') {
						$this->x += ($w/2) + ($offset);
					}
					else {
						$this->x += ($offset) + ($cellFontHeight/3)+($padding['L'] + $border_details['L']['w']);
					}
					$str = '';
					foreach($tablehf['textbuffer'] AS $t) { $str .= $t[0].' '; }
					$str = trim($str);

					if (!isset($va) || $va=='M') {
						$this->y -= ($h-$mih)/2; //Undo what was added earlier VERTICAL ALIGN
						if ($angle > 0) { $this->y += (($h-$mih)/2)+($padding['T'] + $border_details['T']['w']) + ($mih-($padding['T'] + $border_details['T']['w']+$border_details['B']['w']+$padding['B'])); }
						else if ($angle < 0) { $this->y += (($h-$mih)/2)+($padding['T'] + $border_details['T']['w']); }
					}
					else if (isset($va) && $va=='B') {
						$this->y -= $h-$mih; //Undo what was added earlier VERTICAL ALIGN
						if ($angle > 0) { $this->y += $h-($border_details['B']['w']+$padding['B']); }
						else if ($angle < 0) { $this->y += $h-$mih+($padding['T'] + $border_details['T']['w']); }
					}
					else if (isset($va) && $va=='T') {
						if ($angle > 0) { $this->y += $mih-($border_details['B']['w']+$padding['B']); }
						else if ($angle < 0) { $this->y += ($padding['T'] + $border_details['T']['w']); }
					}

					$this->Rotate($angle,$this->x,$this->y);
					$s_fs = $this->FontSizePt;
					$s_f = $this->FontFamily;
					$s_st = $this->FontStyle;
					if (!empty($textbuffer[0][3])) { //Font Color
						$cor = $textbuffer[0][3];
						$this->SetTColor($cor);
					}
					$s_str = $this->strike;
					$this->strike = $textbuffer[0][8]; //Strikethrough
					$this->SetFont($textbuffer[0][4],$textbuffer[0][2],$cellPtSize,true,true);
					$this->Text($this->x,$this->y,$str);
					$this->Rotate(0);
					$this->SetFont($s_f,$s_st,$s_fs,true,true);
					$this->SetTColor(0);
					$this->strike = $s_str;
					$this->x = $opx;
					$this->y = $opy;
		}
		else {
			if ($table['borders_separate']) {	// NB twice border width
				$xadj = $border_details['L']['w'] + $padding['L'] +($table['border_spacing_H']/2);
				$wadj = $border_details['L']['w'] + $border_details['R']['w'] + $padding['L'] +$padding['R'] + $table['border_spacing_H'];
				$yadj = $border_details['T']['w'] + $padding['T'] + ($table['border_spacing_H']/2);
			}
			else {
				$xadj = $border_details['L']['w']/2 + $padding['L'];
				$wadj = ($border_details['L']['w'] + $border_details['R']['w'])/2 + $padding['L'] + $padding['R'];
				$yadj = $border_details['T']['w']/2 + $padding['T'];
			}

			$this->divwidth=$w-($wadj);
			$this->x += $xadj;
			$this->y += $yadj;
			$this->printbuffer($textbuffer,'',true);
		}

	}
      $textbuffer = array();


	// TABLE BORDER - if separate OR collapsed and only table border
	if (($table['borders_separate'] || ($this->simpleTables && !$table['simple']['border'])) && $table['border']) {
			$halfspaceL = $table['padding']['L'] + ($table['border_spacing_H']/2);
			$halfspaceR = $table['padding']['R'] + ($table['border_spacing_H']/2);
			$halfspaceT = $table['padding']['T'] + ($table['border_spacing_V']/2);
			$halfspaceB = $table['padding']['B'] + ($table['border_spacing_V']/2);
			$tbx = $x;
			$tby = $y;
			$tbw = $w;
			$tbh = $h;
			$tab_bord = 0;
			$corner = '';
 			if ($i == $firstrow && $horf=='H') {		// Top
				$tby -= $halfspaceT + ($table['border_details']['T']['w']/2);
				$tbh += $halfspaceT + ($table['border_details']['T']['w']/2);
				$this->setBorder($tab_bord , _BORDER_TOP);
				$corner .= 'T';
			}
			if (($i == ($lastrow) || (isset($tablehf['rowspan']) && ($i+$tablehf['rowspan']) == ($lastrow+1))) && $horf=='F') {	// Bottom
				$tbh += $halfspaceB + ($table['border_details']['B']['w']/2);
				$this->setBorder($tab_bord , _BORDER_BOTTOM);
				$corner .= 'B';
			}
			if ($colctr == 1 && $firstSpread) {	// Left
				$tbx -= $halfspaceL + ($table['border_details']['L']['w']/2);
				$tbw += $halfspaceL + ($table['border_details']['L']['w']/2);
				$this->setBorder($tab_bord , _BORDER_LEFT);
				$corner .= 'L';
			}
			if ($colctr == count($content[$i]) && $finalSpread) {	// Right
				$tbw += $halfspaceR + ($table['border_details']['R']['w']/2);
				$this->setBorder($tab_bord , _BORDER_RIGHT);
				$corner .= 'R';
			}
			$this->_tableRect($tbx, $tby, $tbw, $tbh, $tab_bord , $table['border_details'], false, $table['borders_separate'], 'table', $corner, $table['border_spacing_V'], $table['border_spacing_H'] );
	}


     }// end column $content
     $this->y = $y + $h; //Update y coordinate
   }// end row $i
   unset($table );
   $this->colsums = array();
  }
}


function DefHeaderByName($name,$arr) {
	if (!$name) { $name = '_default'; }
	$this->pageheaders[$name] = $arr;
}

function DefFooterByName($name,$arr) {
	if (!$name) { $name = '_default'; }
	$this->pagefooters[$name] = $arr;
}

function SetHeaderByName($name,$side='O',$write=false) {
	if (!$name) { $name = '_default'; }
	if ($side=='E') { $this->headerDetails['even'] = $this->pageheaders[$name]; }
	else { $this->headerDetails['odd'] = $this->pageheaders[$name]; }
	if ($write) { $this->Header(); }
}

function SetFooterByName($name,$side='O') {
	if (!$name) { $name = '_default'; }
	if ($side=='E') { $this->footerDetails['even'] = $this->pagefooters[$name]; }
	else { $this->footerDetails['odd'] = $this->pagefooters[$name]; }
}



function SetHeader($Harray=array(),$side='',$write=false) {
  if (is_string($Harray)) {
    if (strlen($Harray)==0) {
	if ($side=='O') { $this->headerDetails['odd'] = array(); }
	else if ($side=='E') { $this->headerDetails['even'] = array(); }
	else { $this->headerDetails = array(); }
   }
   else if (strpos($Harray,'|') || strpos($Harray,'|')===0) {
	$hdet = explode('|',$Harray);
	$this->headerDetails = array (
  		'odd' => array (
	'L' => array ('content' => $hdet[0], 'font-size' => $this->defaultheaderfontsize, 'font-style' => $this->defaultheaderfontstyle),
	'C' => array ('content' => $hdet[1], 'font-size' => $this->defaultheaderfontsize, 'font-style' => $this->defaultheaderfontstyle),
	'R' => array ('content' => $hdet[2], 'font-size' => $this->defaultheaderfontsize, 'font-style' => $this->defaultheaderfontstyle),
	'line' => $this->defaultheaderline,
  		),
  		'even' => array (
	'R' => array ('content' => $hdet[0], 'font-size' => $this->defaultheaderfontsize, 'font-style' => $this->defaultheaderfontstyle),
	'C' => array ('content' => $hdet[1], 'font-size' => $this->defaultheaderfontsize, 'font-style' => $this->defaultheaderfontstyle),
	'L' => array ('content' => $hdet[2], 'font-size' => $this->defaultheaderfontsize, 'font-style' => $this->defaultheaderfontstyle),
	'line' => $this->defaultheaderline,
		)
	);
    }
    else {
	$this->headerDetails = array (
  		'odd' => array (
	'R' => array ('content' => $Harray, 'font-size' => $this->defaultheaderfontsize, 'font-style' => $this->defaultheaderfontstyle),
	'line' => $this->defaultheaderline,
  		),
  		'even' => array (
	'L' => array ('content' => $Harray, 'font-size' => $this->defaultheaderfontsize, 'font-style' => $this->defaultheaderfontstyle),
	'line' => $this->defaultheaderline,
		)
	);
    }
  }
  else if (is_array($Harray)) {
	if ($side=='O') { $this->headerDetails['odd'] = $Harray; }
	else if ($side=='E') { $this->headerDetails['even'] = $Harray; }
	else { $this->headerDetails = $Harray; }
  }

  if ($write) {
	$save_y = $this->y;
	$this->Header();
	$this->SetY($save_y) ;
  }
}




function SetFooter($Farray=array(),$side='') {
  if (is_string($Farray)) {
    if (strlen($Farray)==0) {
	if ($side=='O') { $this->footerDetails['odd'] = array(); }
	else if ($side=='E') { $this->footerDetails['even'] = array(); }
	else { $this->footerDetails = array(); }
    }
    else if (strpos($Farray,'|') || strpos($Farray,'|')===0) {
	$fdet = explode('|',$Farray);
	$this->footerDetails = array (
		'odd' => array (
	'L' => array ('content' => $fdet[0], 'font-size' => $this->defaultfooterfontsize, 'font-style' => $this->defaultfooterfontstyle),
	'C' => array ('content' => $fdet[1], 'font-size' => $this->defaultfooterfontsize, 'font-style' => $this->defaultfooterfontstyle),
	'R' => array ('content' => $fdet[2], 'font-size' => $this->defaultfooterfontsize, 'font-style' => $this->defaultfooterfontstyle),
	'line' => $this->defaultfooterline,
		),
		'even' => array (
	'R' => array ('content' => $fdet[0], 'font-size' => $this->defaultfooterfontsize, 'font-style' => $this->defaultfooterfontstyle),
	'C' => array ('content' => $fdet[1], 'font-size' => $this->defaultfooterfontsize, 'font-style' => $this->defaultfooterfontstyle),
	'L' => array ('content' => $fdet[2], 'font-size' => $this->defaultfooterfontsize, 'font-style' => $this->defaultfooterfontstyle),
	'line' => $this->defaultfooterline,
		)
	);
    }
    else {
	$this->footerDetails = array (
		'odd' => array (
	'R' => array ('content' => $Farray, 'font-size' => $this->defaultfooterfontsize, 'font-style' => $this->defaultfooterfontstyle),
	'line' => $this->defaultfooterline,
		),
		'even' => array (
	'L' => array ('content' => $Farray, 'font-size' => $this->defaultfooterfontsize, 'font-style' => $this->defaultfooterfontstyle),
	'line' => $this->defaultfooterline,
		)
	);
    }
  }
  else if (is_array($Farray)) {
	if ($side=='O') { $this->footerDetails['odd'] = $Farray; }
	else if ($side=='E') { $this->footerDetails['even'] = $Farray; }
	else { $this->footerDetails = $Farray; }
  }
}



//Page footer
function Footer() {




  $this->processingHeader=true;
  $this->ResetMargins();	// necessary after columns
  $this->pgwidth = $this->w - $this->lMargin - $this->rMargin;
  $h = $this->footerDetails;
  if(count($h)) {

	if ($this->forcePortraitHeaders && $this->CurOrientation=='L' && $this->CurOrientation!=$this->DefOrientation) {
		$this->_out(sprintf('q 0 -1 1 0 0 %.3F cm ',($this->h*_MPDFK)));
		$headerpgwidth = $this->h - $this->orig_lMargin - $this->orig_rMargin;
		if (($this->mirrorMargins) && (($this->page)%2==0)) {	// EVEN
			$headerlmargin = $this->orig_rMargin;
		}
		else {
			$headerlmargin = $this->orig_lMargin;
		}
	}
	else {
		$yadj = 0;
		$headerpgwidth = $this->pgwidth;
		$headerlmargin = $this->lMargin;
	}
	$this->SetY(-$this->margin_footer);

	$this->SetTColor($this->ConvertColor(0));
    	$this->SUP = false;
	$this->SUB = false;
	$this->bullet = false;

	// only show pagenumber if numbering on
	$pgno = $this->docPageNum($this->page, true);

	if (($this->mirrorMargins) && (($this->page)%2==0)) {	// EVEN
			$side = 'even';
	}
	else {	// ODD	// OR NOT MIRRORING MARGINS/FOOTERS = DEFAULT
			$side = 'odd';
	}
	$maxfontheight = 0;
	foreach(array('L','C','R') AS $pos) {
	  if (isset($h[$side][$pos]['content']) && $h[$side][$pos]['content']) {
		if (isset($h[$side][$pos]['font-size']) && $h[$side][$pos]['font-size']) { $hfsz = $h[$side][$pos]['font-size']; }
		else { $hfsz = $this->default_font_size; }
		$maxfontheight = max($maxfontheight,$hfsz);
	  }
	}
	// LEFT-CENTER-RIGHT
	foreach(array('L','C','R') AS $pos) {
	  if (isset($h[$side][$pos]['content']) && $h[$side][$pos]['content']) {
		$hd = str_replace('{PAGENO}',$pgno,$h[$side][$pos]['content']);
		$hd = str_replace($this->aliasNbPgGp,$this->nbpgPrefix.$this->aliasNbPgGp.$this->nbpgSuffix,$hd);
		$hd = preg_replace('/\{DATE\s+(.*?)\}/e',"date('\\1')",$hd);
		if (isset($h[$side][$pos]['font-family']) && $h[$side][$pos]['font-family']) { $hff = $h[$side][$pos]['font-family']; }
		else { $hff = $this->original_default_font; }
		if (isset($h[$side][$pos]['font-size']) && $h[$side][$pos]['font-size']) { $hfsz = $h[$side][$pos]['font-size']; }
		else { $hfsz = $this->original_default_font_size; }
		$maxfontheight = max($maxfontheight,$hfsz);
		if (isset($h[$side][$pos]['font-style']) && $h[$side][$pos]['font-style']) { $hfst = $h[$side][$pos]['font-style']; }
		else { $hfst = ''; }
		if (isset($h[$side][$pos]['color']) && $h[$side][$pos]['color']) {
			$hfcol = $h[$side][$pos]['color'];
			$cor = $this->ConvertColor($hfcol);
			if ($cor) { $this->SetTColor($cor); }
		}
		else { $hfcol = ''; }
		$this->SetFont($hff,$hfst,$hfsz,true,true);
		$this->x = $headerlmargin ;
		$this->y = $this->h - $this->margin_footer - ($maxfontheight/_MPDFK);
		$hd = $this->purify_utf8_text($hd);
		if ($this->text_input_as_HTML) {
			$hd = $this->all_entities_to_utf8($hd);
		}
		// CONVERT CODEPAGE
		if ($this->usingCoreFont) { $hd = mb_convert_encoding($hd,$this->mb_enc,'UTF-8'); }
		// DIRECTIONALITY RTL
		// Font-specific ligature substitution for Indic fonts
		$align = $pos;
		if ($this->directionality == 'rtl') {
			if ($pos == 'L') { $align = 'R'; }
			else if ($pos == 'R') { $align = 'L'; }
		}

		if ($pos!='L' && (strpos($hd,$this->aliasNbPg)!==false || strpos($hd,$this->aliasNbPgGp)!==false)) {
			if (strpos($hd,$this->aliasNbPgGp)!==false) { $type= 'nbpggp'; } else { $type= 'nbpg'; }
			$this->_out('{mpdfheader'.$type.' '.$pos.' ff='.$hff.' fs='.$hfst.' fz='.$hfsz.'}');
			$this->Cell($headerpgwidth ,$maxfontheight/_MPDFK ,$hd,0,0,$align,0,'',0,0,0,'M');
			$this->_out('Q');
		}
		else {
			$this->Cell($headerpgwidth ,$maxfontheight/_MPDFK ,$hd,0,0,$align,0,'',0,0,0,'M');
		}
		if ($hfcol) { $this->SetTColor($this->ConvertColor(0)); }
	  }
	}
	// Return Font to normal
	$this->SetFont($this->default_font,'',$this->original_default_font_size);

	// LINE

	if (isset($h[$side]['line']) && $h[$side]['line']) {
		$this->SetLineWidth(0.1);
		$this->SetDColor($this->ConvertColor(0));
		$this->Line($headerlmargin , $this->y-($maxfontheight*($this->footer_line_spacing)/_MPDFK), $headerlmargin +$headerpgwidth, $this->y-($maxfontheight*($this->footer_line_spacing)/_MPDFK));
	}
	if ($this->forcePortraitHeaders && $this->CurOrientation=='L' && $this->CurOrientation!=$this->DefOrientation) {
		$this->_out('Q');
	}
  }
  $this->processingHeader=false;

}



///////////////////
/// HTML parser ///
///////////////////
function WriteHTML($html,$sub=0,$init=true,$close=true) {
				// $sub ADDED - 0 = default; 1=headerCSS only; 2=HTML body (parts) only; 3 - HTML parses only
				// 4 - writes HTML headers
				// $close Leaves buffers etc. in current state, so that it can continue a block etc.
				// $init - Clears and sets buffers to Top level block etc.

	if (empty($html)) { $html = ''; }

	if ($init) {
		$this->headerbuffer='';
		$this->textbuffer = array();
		$this->fixedPosBlockSave = array();
	}
	if ($sub == 1) { $html = '<style> '.$html.' </style>'; }	// stylesheet only

	if ($this->allow_charset_conversion) {
		if ($sub < 1) {
			$this->ReadCharset($html);
		}
		if ($this->charset_in && $sub!=4) {	// mPDF 5.4.14
			$success = iconv($this->charset_in,'UTF-8//TRANSLIT',$html);
			if ($success) { $html = $success; }
		}
	}
	$html = $this->purify_utf8($html,false);
	if ($init) {
		$this->blklvl = 0;
		$this->lastblocklevelchange = 0;
		$this->blk = array();
		$this->initialiseBlock($this->blk[0]);
		$this->blk[0]['width'] =& $this->pgwidth;
		$this->blk[0]['inner_width'] =& $this->pgwidth;
		$this->blk[0]['blockContext'] = $this->blockContext;
	}

	$zproperties = array();
	if ($sub < 2) {
		$this->ReadMetaTags($html);

		// NB default stylesheet now in mPDF.css - read on initialising class
		$html = $this->ReadCSS($html);

		if ($this->useLang && !$this->usingCoreFont && preg_match('/<html [^>]*lang=[\'\"](.*?)[\'\"]/ism',$html,$m)) {
			$html_lang = $m[1];
		}

		if (preg_match('/<html [^>]*dir=[\'\"]\s*rtl\s*[\'\"]/ism',$html)) {
			$zproperties['DIRECTION'] = 'rtl';
		}

		// allow in-line CSS for body tag to be parsed // Get <body> tag inline CSS
		if (preg_match('/<body([^>]*)>(.*?)<\/body>/ism',$html,$m) || preg_match('/<body([^>]*)>(.*)$/ism',$html,$m)) {
			$html = $m[2];
			// Changed to allow style="background: url('bg.jpg')"
			if (preg_match('/style=[\"](.*?)[\"]/ism',$m[1],$mm) || preg_match('/style=[\'](.*?)[\']/ism',$m[1],$mm)) {
				$zproperties = $this->readInlineCSS($mm[1]);
			}
			if (preg_match('/dir=[\'\"]\s*rtl\s*[\'\"]/ism',$m[1])) {
				$zproperties['DIRECTION'] = 'rtl';
			}
			if (isset($html_lang) && $html_lang) { $zproperties['LANG'] = $html_lang; }
			if ($this->useLang && !$this->onlyCoreFonts && preg_match('/lang=[\'\"](.*?)[\'\"]/ism',$m[1],$mm)) {
				$zproperties['LANG'] = $mm[1];
			}

		}
	}
	$properties = $this->MergeCSS('BLOCK','BODY','');
	if ($zproperties) { $properties = $this->array_merge_recursive_unique($properties,$zproperties); }

	if (isset($properties['DIRECTION']) && $properties['DIRECTION']) {
		$this->CSS['BODY']['DIRECTION'] = $properties['DIRECTION'];
	}
	if (!isset($this->CSS['BODY']['DIRECTION'])) {
		$this->CSS['BODY']['DIRECTION'] = $this->directionality;
	}
	else { $this->SetDirectionality($this->CSS['BODY']['DIRECTION']); }

	$this->setCSS($properties,'','BODY');
	$this->blk[0]['InlineProperties'] = $this->saveInlineProperties();

	if ($sub == 1) { return ''; }
	if (!isset($this->CSS['BODY'])) { $this->CSS['BODY'] = array(); }



	$parseonly = false;
	$this->bufferoutput = false;
	if ($sub == 3) {
		$parseonly = true;
		// Close any open block tags
		for ($b= $this->blklvl;$b>0;$b--) { $this->CloseTag($this->blk[$b]['tag']); }
		// Output any text left in buffer
		if (count($this->textbuffer)) { $this->printbuffer($this->textbuffer); }
		$this->textbuffer=array();
	}
	else if ($sub == 4) {
		// Close any open block tags
		for ($b= $this->blklvl;$b>0;$b--) { $this->CloseTag($this->blk[$b]['tag']); }
		// Output any text left in buffer
		if (count($this->textbuffer)) { $this->printbuffer($this->textbuffer); }
		$this->bufferoutput = true;
		$this->textbuffer=array();
		$this->headerbuffer='';
		$properties = $this->MergeCSS('BLOCK','BODY','');
		$this->setCSS($properties,'','BODY');
	}

	mb_internal_encoding('UTF-8');

	$html = $this->AdjustHTML($html, $this->tabSpaces); //Try to make HTML look more like XHTML

	if ($this->autoFontGroups) { $html = $this->AutoFont($html); }

	$html = preg_replace('/<htmlpageheader.*?<\/htmlpageheader>/si','',$html);
	$html = preg_replace('/<htmlpagefooter.*?<\/htmlpagefooter>/si','',$html);

	if($this->state==0 && $sub!=1 && $sub!=3 && $sub!=4) {
		$this->AddPage($this->CurOrientation);
	}



	$html=str_replace('<?','< ',$html); //Fix '<?XML' bug from HTML code generated by MS Word

	$this->checkSIP = false;
	$this->checkSMP = false;
	$this->checkCJK = false;
	if ($this->onlyCoreFonts) { $html = $this->SubstituteChars($html); }
	else {
		if (preg_match("/([\x{20000}-\x{2FFFF}])/u", $html)) { $this->checkSIP = true; }
		if (preg_match("/([\x{10000}-\x{1FFFF}])/u", $html)) { $this->checkSMP = true; }
	}

	// Don't allow non-breaking spaces that are converted to substituted chars or will break anyway and mess up table width calc.
	$html = str_replace('<tta>160</tta>',chr(32),$html);
	$html = str_replace('</tta><tta>','|',$html);
	$html = str_replace('</tts><tts>','|',$html);
	$html = str_replace('</ttz><ttz>','|',$html);

	//Add new supported tags in the DisableTags function
	$html=strip_tags($html,$this->enabledtags); //remove all unsupported tags, but the ones inside the 'enabledtags' string

	//Explode the string in order to parse the HTML code
	$a=preg_split('/<(.*?)>/ms',$html,-1,PREG_SPLIT_DELIM_CAPTURE);
	// ? more accurate regexp that allows e.g. <a name="Silly <name>">
	// if changing - also change in fn.SubstituteChars()
	// $a = preg_split ('/<((?:[^<>]+(?:"[^"]*"|\'[^\']*\')?)+)>/ms', $html, -1, PREG_SPLIT_DELIM_CAPTURE);

	if ($this->mb_enc) {
		mb_internal_encoding($this->mb_enc);
	}
	$pbc = 0;
	$this->subPos = -1;
	$cnt = count($a);
	for($i=0;$i<$cnt; $i++) {
		$e = $a[$i];
		if($i%2==0) {
		//TEXT
			if ($this->blk[$this->blklvl]['hide']) { continue; }
			if ($this->inlineDisplayOff) { continue; }
			if ($this->inMeter) { continue; }	// mPDF 5.5.09

			if (strlen($e) == 0) { continue; }

			$e = strcode2utf($e);
			$e = $this->lesser_entity_decode($e);

			if ($this->usingCoreFont) {
				// If core font is selected in document which is not onlyCoreFonts - substitute with non-core font
				if ($this->useSubstitutions && !$this->onlyCoreFonts && $this->subPos<$i && !$this->specialcontent) {
					$cnt += $this->SubstituteCharsNonCore($a, $i, $e);
				}
				// CONVERT ENCODING
				$e = mb_convert_encoding($e,$this->mb_enc,'UTF-8');
				if ($this->toupper) { $e = strtoupper($e); }
				if ($this->tolower) { $e = strtolower($e); }
				if ($this->capitalize) { $e = ucwords($e); }
			}
			else {
				if ($this->checkSIP && $this->CurrentFont['sipext'] && $this->subPos<$i && !$this->specialcontent) {
					$cnt += $this->SubstituteCharsSIP($a, $i, $e);
				}
				if ($this->useSubstitutions && !$this->onlyCoreFonts && $this->CurrentFont['type']!='Type0' && $this->subPos<$i && !$this->specialcontent) {
					$cnt += $this->SubstituteCharsMB($a, $i, $e);
				}
					// mPDF 5.4.05	removes U+200E/U+200F LTR and RTL mark
					// mPDF 5.4.06	removes U+200C/U+200D Zero-width Joiner and Non-joiner
					$e = preg_replace("/[\xe2\x80\x8c\xe2\x80\x8d\xe2\x80\x8e\xe2\x80\x8f]/u",'',$e);
				// Font-specific ligature substitution for Indic fonts

				if ($this->toupper) { $e = mb_strtoupper($e,$this->mb_enc); }
				if ($this->tolower) { $e = mb_strtolower($e,$this->mb_enc); }
				if ($this->capitalize) { $e = mb_convert_case($e, MB_CASE_TITLE, "UTF-8"); }
			}
			if (($this->tts) || ($this->ttz) || ($this->tta)) {
				$es = explode('|',$e);
				$e = '';
				foreach($es AS $val) {
					$e .= chr($val);
				}
			}
			//Adjust lineheight

			//  FORM ELEMENTS
  			if ($this->specialcontent) {
		      }

			// TABLE
			else if ($this->tableLevel) {
				if ($this->tdbegin) {
     				   if (($this->ignorefollowingspaces) && !$this->ispre) { $e = ltrim($e); }
				   if ($e || $e==='0') {
				      if (($this->blockjustfinished || $this->listjustfinished) && $this->cell[$this->row][$this->col]['s']>0) {
						$this->_saveCellTextBuffer("\n");
						if (!isset($this->cell[$this->row][$this->col]['maxs'])) {
							$this->cell[$this->row][$this->col]['maxs'] = $this->cell[$this->row][$this->col]['s'];
						}
						elseif($this->cell[$this->row][$this->col]['maxs'] < $this->cell[$this->row][$this->col]['s']) {
							$this->cell[$this->row][$this->col]['maxs'] = $this->cell[$this->row][$this->col]['s'];
						}
						$this->cell[$this->row][$this->col]['s'] = 0;// reset
				      }
					$this->blockjustfinished=false;
					$this->listjustfinished=false;

					$this->_saveCellTextBuffer($e, $this->HREF);

          				if (!isset($this->cell[$this->row][$this->col]['R']) || !$this->cell[$this->row][$this->col]['R']) {
						if (isset($this->cell[$this->row][$this->col]['s'])) {
							$this->cell[$this->row][$this->col]['s'] += $this->GetStringWidth($e, false);
						}
						else { $this->cell[$this->row][$this->col]['s'] = $this->GetStringWidth($e, false); }
						if (!empty($this->spanborddet)) {
							$this->cell[$this->row][$this->col]['s'] += $this->spanborddet['L']['w'] + $this->spanborddet['R']['w'];
						}
					}


					if ($this->tableLevel==1 && $this->useGraphs) {
						$this->graphs[$this->currentGraphId]['data'][$this->row][$this->col] = $e;
					}
					$this->nestedtablejustfinished = false;
					$this->linebreakjustfinished=false;
				   }
				}
			}
			// ALL ELSE
			else {
    				if ($this->ignorefollowingspaces and !$this->ispre) { $e = ltrim($e); }
				if ($e || $e==='0') $this->_saveTextBuffer($e, $this->HREF);
			}
		}


		else { // TAG **
		   
		   if($e[0]=='/') {


		    // Check for tags where HTML specifies optional end tags,
    		    // and/or does not allow nesting e.g. P inside P, or
		    $endtag = trim(strtoupper(substr($e,1)));	// mPDF 5.4.20
		    if($this->blk[$this->blklvl]['hide']) {
			if (in_array($endtag, $this->outerblocktags) || in_array($endtag, $this->innerblocktags)) {
				unset($this->blk[$this->blklvl]);
				$this->blklvl--;
			}
			continue;
		    }

		    if ($this->allow_html_optional_endtags && !$parseonly) {
			if (($endtag == 'DIV' || $endtag =='FORM' || $endtag =='CENTER') && $this->lastoptionaltag == 'P') { $this->CloseTag($this->lastoptionaltag ); }
			if ($this->lastoptionaltag == 'LI' && $endtag == 'OL') { $this->CloseTag($this->lastoptionaltag ); }
			if ($this->lastoptionaltag == 'LI' && $endtag == 'UL') { $this->CloseTag($this->lastoptionaltag ); }
			if ($this->lastoptionaltag == 'DD' && $endtag == 'DL') { $this->CloseTag($this->lastoptionaltag ); }
			if ($this->lastoptionaltag == 'DT' && $endtag == 'DL') { $this->CloseTag($this->lastoptionaltag ); }
			if ($this->lastoptionaltag == 'OPTION' && $endtag == 'SELECT') { $this->CloseTag($this->lastoptionaltag ); }
			if ($endtag == 'TABLE') {
				if ($this->lastoptionaltag == 'THEAD' || $this->lastoptionaltag == 'TBODY' || $this->lastoptionaltag == 'TFOOT') {
					$this->CloseTag($this->lastoptionaltag);
				}
				if ($this->lastoptionaltag == 'TR') { $this->CloseTag('TR'); }
				if ($this->lastoptionaltag == 'TD' || $this->lastoptionaltag == 'TH') { $this->CloseTag($this->lastoptionaltag ); $this->CloseTag('TR'); }
			}
			if ($endtag == 'THEAD' || $endtag == 'TBODY' || $endtag == 'TFOOT') {
				if ($this->lastoptionaltag == 'TR') { $this->CloseTag('TR'); }
				if ($this->lastoptionaltag == 'TD' || $this->lastoptionaltag == 'TH') { $this->CloseTag($this->lastoptionaltag ); $this->CloseTag('TR'); }
			}
			if ($endtag == 'TR') {
				if ($this->lastoptionaltag == 'TD' || $this->lastoptionaltag == 'TH') { $this->CloseTag($this->lastoptionaltag ); }
			}
		    }
		    $this->CloseTag($endtag);
		   }

		   else {	// OPENING TAG
			if($this->blk[$this->blklvl]['hide']) {
				if (strpos($e,' ')) { $te = strtoupper(substr($e,0,strpos($e,' '))); }
				else { $te = strtoupper($e); }
				if (in_array($te, $this->outerblocktags) || in_array($te, $this->innerblocktags)) {
					$this->blklvl++;
 					$this->blk[$this->blklvl]['hide']=true;
				}
				continue;
			}

			$regexp = '|=\'(.*?)\'|s'; // eliminate single quotes, if any
      		$e = preg_replace($regexp,"=\"\$1\"",$e);
			// changes anykey=anyvalue to anykey="anyvalue" (only do this inside tags)
			if (substr($e,0,10)!='pageheader' && substr($e,0,10)!='pagefooter') {
				$regexp = '| (\\w+?)=([^\\s>"]+)|si';
	      		$e = preg_replace($regexp," \$1=\"\$2\"",$e);
			}

      		$e = preg_replace('/ (\\S+?)\s*=\s*"/i', " \\1=\"", $e);

      		//Fix path values, if needed
			$orig_srcpath = '';
			if ((stristr($e,"href=") !== false) or (stristr($e,"src=") !== false) ) {
				$regexp = '/ (href|src)\s*=\s*"(.*?)"/i';
				preg_match($regexp,$e,$auxiliararray);
				if (isset($auxiliararray[2])) { $path = $auxiliararray[2]; }
				else { $path = ''; }
				if (trim($path) != '' && !(stristr($e,"src=") !== false && substr($path,0,4)=='var:')) {
					$orig_srcpath = $path;
					$this->GetFullPath($path);
					$regexp = '/ (href|src)="(.*?)"/i';
					$e = preg_replace($regexp,' \\1="'.$path.'"',$e);
				}
			}//END of Fix path values


			//Extract attributes
			$contents=array();
			$contents1=array();	// mPDF 5.5.17
			$contents2=array();
			// Changed to allow style="background: url('bg.jpg')"
			// mPDF 5.5.17  Changed to improve performance; maximum length of \S (attribute) = 16
			preg_match_all('/\\S{1,16}=["][^"]*["]/',$e,$contents1);
			preg_match_all('/\\S{1,16}=[\'][^\']*[\']/i',$e,$contents2);

			$contents = array_merge($contents1, $contents2);
			preg_match('/\\S+/',$e,$a2);
			$tag=strtoupper($a2[0]);
			$attr=array();
			if ($orig_srcpath) { $attr['ORIG_SRC'] = $orig_srcpath; }
			if (!empty($contents)) {
				foreach($contents[0] as $v) {
					// Changed to allow style="background: url('bg.jpg')"
 					if(preg_match('/^([^=]*)=["]?([^"]*)["]?$/',$v,$a3) || preg_match('/^([^=]*)=[\']?([^\']*)[\']?$/',$v,$a3)) {
 						if (strtoupper($a3[1])=='ID' || strtoupper($a3[1])=='CLASS') {	// 4.2.013 Omits STYLE
   							$attr[strtoupper($a3[1])]=trim(strtoupper($a3[2]));
						}
						// includes header-style-right etc. used for <pageheader>
 						else if (preg_match('/^(HEADER|FOOTER)-STYLE/i',$a3[1])) {
   							$attr[strtoupper($a3[1])]=trim(strtoupper($a3[2]));
						}
						else {
    							$attr[strtoupper($a3[1])]=trim($a3[2]);
						}
     					}
  				}
			}
			$this->OpenTag($tag,$attr);
		      // mPDF 5.5.09
		      if (preg_match('/\/$/',$e)) { $this->closeTag($tag); }

		   }

		} // end TAG
	} //end of	foreach($a as $i=>$e)

	if ($close) {

		// Close any open block tags
		for ($b= $this->blklvl;$b>0;$b--) { $this->CloseTag($this->blk[$b]['tag']); }

		// Output any text left in buffer
		if (count($this->textbuffer) && !$parseonly) { $this->printbuffer($this->textbuffer); }
		if (!$parseonly) $this->textbuffer=array();



		//Create Internal Links, if needed
		if (!empty($this->internallink) ) {
			foreach($this->internallink as $k=>$v) {
				if (strpos($k,"#") !== false ) { continue; } //ignore
				$ypos = $v['Y'];
				$pagenum = $v['PAGE'];
				$sharp = "#";
				while (array_key_exists($sharp.$k,$this->internallink)) {
					$internallink = $this->internallink[$sharp.$k];
					$this->SetLink($internallink,$ypos,$pagenum);
					$sharp .= "#";
				}
			}
		}

		$this->linemaxfontsize = '';
		$this->lineheight_correction = $this->default_lineheight_correction;

		$this->bufferoutput = false;


	}
}




function initialiseBlock(&$blk) {
	$blk['margin_top'] = 0;
	$blk['margin_left'] = 0;
	$blk['margin_bottom'] = 0;
	$blk['margin_right'] = 0;
	$blk['padding_top'] = 0;
	$blk['padding_left'] = 0;
	$blk['padding_bottom'] = 0;
	$blk['padding_right'] = 0;
	$blk['border_top']['w'] = 0;
	$blk['border_left']['w'] = 0;
	$blk['border_bottom']['w'] = 0;
	$blk['border_right']['w'] = 0;
	$blk['hide'] = false;
	$blk['outer_left_margin'] = 0;
	$blk['outer_right_margin'] = 0;
	$blk['cascadeCSS'] = array();
	$blk['block-align'] = false;
	$blk['bgcolor'] = false;
	$blk['page_break_after_avoid'] = false;
	$blk['keep_block_together'] = false;
	$blk['float'] = false;
	$blk['line_height'] = '';
	$blk['margin_collapse'] = false;
}


function border_details($bd) {
	$prop = preg_split('/\s+/',trim($bd));

	if (isset($this->blk[$this->blklvl]['inner_width'])) { $refw = $this->blk[$this->blklvl]['inner_width']; }
	else if (isset($this->blk[$this->blklvl-1]['inner_width'])) { $refw = $this->blk[$this->blklvl-1]['inner_width']; }
	else { $refw = $this->w; }
	if ( count($prop) == 1 ) {
		$bsize = $this->ConvertSize($prop[0],$refw,$this->FontSize,false);
		if ($bsize > 0) {
			return array('s' => 1, 'w' => $bsize, 'c' => $this->ConvertColor(0), 'style'=>'solid');
		}
		else { return array('w' => 0, 's' => 0); }
	}

	else if (count($prop) == 2 ) {
		// 1px solid
		if (in_array($prop[1],$this->borderstyles) || $prop[1] == 'none' || $prop[1] == 'hidden' ) { $prop[2] = ''; }
		// solid #000000
		else if (in_array($prop[0],$this->borderstyles) || $prop[0] == 'none' || $prop[0] == 'hidden' ) { $prop[0] = ''; $prop[1] = $prop[0]; $prop[2] = $prop[1]; }
		// 1px #000000
		else { $prop[1] = ''; $prop[2] = $prop[1]; }
	}
	else if ( count($prop) == 3 ) {
		// Change #000000 1px solid to 1px solid #000000 (proper)
		if (substr($prop[0],0,1) == '#') { $tmp = $prop[0]; $prop[0] = $prop[1]; $prop[1] = $prop[2]; $prop[2] = $tmp; }
		// Change solid #000000 1px to 1px solid #000000 (proper)
		else if (substr($prop[0],1,1) == '#') { $tmp = $prop[1]; $prop[0] = $prop[2]; $prop[1] = $prop[0]; $prop[2] = $tmp; }
		// Change solid 1px #000000 to 1px solid #000000 (proper)
		else if (in_array($prop[0],$this->borderstyles) || $prop[0] == 'none' || $prop[0] == 'hidden' ) {
			$tmp = $prop[0]; $prop[0] = $prop[1]; $prop[1] = $tmp;
		}
	}
	else { return array(); }
	// Size
	$bsize = $this->ConvertSize($prop[0],$refw,$this->FontSize,false);
	//color
	$coul = $this->ConvertColor($prop[2]);	// returns array
	// Style
	$prop[1] = strtolower($prop[1]);
	if (in_array($prop[1],$this->borderstyles) && $bsize > 0) { $on = 1; }
	else if ($prop[1] == 'hidden') { $on = 1; $bsize = 0; $coul = ''; }
	else if ($prop[1] == 'none') { $on = 0; $bsize = 0; $coul = ''; }
	else { $on = 0; $bsize = 0; $coul = ''; $prop[1] = ''; }
	return array('s' => $on, 'w' => $bsize, 'c' => $coul, 'style'=> $prop[1] );
}


function _fix_borderStr($bd) {
	// mPDF 5.5.04
	preg_match_all("/\((.*?)\)/", $bd, $m);
	if (count($m[1])) {
		for($i=0;$i<count($m[1]);$i++) {
			$sub = preg_replace("/ /", "", $m[1][$i]);
			$bd = preg_replace('/'.preg_quote($m[1][$i], '/').'/si', $sub, $bd);
		}
	}

	$prop = preg_split('/\s+/',trim($bd));
	$w = 'medium';
	$c = '#000000';
	$s = 'none';

	if ( count($prop) == 1 ) {
		// solid
		if (in_array($prop[0],$this->borderstyles) || $prop[0] == 'none' || $prop[0] == 'hidden' ) { $s = $prop[0]; }
		// #000000
		else if (is_array($this->ConvertColor($prop[0]))) { $c = $prop[0]; }
		// 1px
		else { $w = $prop[0]; }
	}
	else if (count($prop) == 2 ) {
		// 1px solid
		if (in_array($prop[1],$this->borderstyles) || $prop[1] == 'none' || $prop[1] == 'hidden' ) { $w = $prop[0]; $s = $prop[1]; }
		// solid #000000
		else if (in_array($prop[0],$this->borderstyles) || $prop[0] == 'none' || $prop[0] == 'hidden' ) { $s = $prop[0]; $c = $prop[1]; }
		// 1px #000000
		else { $w = $prop[0]; $c = $prop[1]; }
	}
	else if ( count($prop) == 3 ) {
		// Change #000000 1px solid to 1px solid #000000 (proper)
		if (substr($prop[0],0,1) == '#') { $c = $prop[0]; $w = $prop[1]; $s = $prop[2]; }
		// Change solid #000000 1px to 1px solid #000000 (proper)
		else if (substr($prop[0],1,1) == '#') { $s = $prop[0]; $c = $prop[1]; $w = $prop[2]; }
		// Change solid 1px #000000 to 1px solid #000000 (proper)
		else if (in_array($prop[0],$this->borderstyles) || $prop[0] == 'none' || $prop[0] == 'hidden' ) {
			$s = $prop[0]; $w = $prop[1]; $c = $prop[2];
		}
		else { $w = $prop[0]; $s = $prop[1]; $c = $prop[2]; }
	}
	else { return ''; }
	$s = strtolower($s);
	return $w.' '.$s.' '.$c;
}



// NEW FUNCTION FOR CSS MARGIN or PADDING called from SetCSS
function fixCSS($prop) {
	if (!is_array($prop) || (count($prop)==0)) return array();
	$newprop = array();
	foreach($prop AS $k => $v) {
		if ($k != 'BACKGROUND-IMAGE' && $k != 'BACKGROUND' && $k != 'ODD-HEADER-NAME' && $k != 'EVEN-HEADER-NAME' && $k != 'ODD-FOOTER-NAME' && $k != 'EVEN-FOOTER-NAME' && $k != 'HEADER' && $k != 'FOOTER') {
			$v = strtolower($v);
		}

		if ($k == 'FONT') {
			$s = trim($v);
			preg_match_all('/\"(.*?)\"/',$s,$ff);
			if (count($ff[1])) {
				foreach($ff[1] AS $ffp) {
					$w = preg_split('/\s+/',$ffp);
					$s = preg_replace('/\"'.$ffp.'\"/',$w[0],$s);
				}
			}
			preg_match_all('/\'(.*?)\'/',$s,$ff);
			if (count($ff[1])) {
				foreach($ff[1] AS $ffp) {
					$w = preg_split('/\s+/',$ffp);
					$s = preg_replace('/\''.$ffp.'\'/',$w[0],$s);
				}
			}
			$s = preg_replace('/\s*,\s*/',',',$s);
			$bits = preg_split('/\s+/',$s);
			if (count($bits)>1) {
				$k = 'FONT-FAMILY'; $v = $bits[(count($bits)-1)];
				$fs = $bits[(count($bits)-2)];
				if (preg_match('/(.*?)\/(.*)/',$fs, $fsp)) {
					$newprop['FONT-SIZE'] = $fsp[1];
					$newprop['LINE-HEIGHT'] = $fsp[2];
				}
				else { $newprop['FONT-SIZE'] = $fs; }
				if (preg_match('/(italic|oblique)/i',$s)) { $newprop['FONT-STYLE'] = 'italic'; }
				else { $newprop['FONT-STYLE'] = 'normal'; }
				if (preg_match('/bold/i',$s)) { $newprop['FONT-WEIGHT'] = 'bold'; }
				else { $newprop['FONT-WEIGHT'] = 'normal'; }
				if (preg_match('/small-caps/i',$s)) { $newprop['TEXT-TRANSFORM'] = 'uppercase'; }
			}
		}
		if ($k == 'FONT-FAMILY') {
			$aux_fontlist = explode(",",$v);
			$found = 0;
			foreach($aux_fontlist AS $f) {
				$fonttype = trim($f);
				$fonttype = preg_replace('/["\']*(.*?)["\']*/','\\1',$fonttype);
				$fonttype = preg_replace('/ /','',$fonttype);
				$v = strtolower(trim($fonttype));
				if (isset($this->fonttrans[$v]) && $this->fonttrans[$v]) { $v = $this->fonttrans[$v]; }
				if ((!$this->onlyCoreFonts && in_array($v,$this->available_unifonts)) ||
					in_array($v,array('ccourier','ctimes','chelvetica')) ||
					($this->onlyCoreFonts && in_array($v,array('courier','times','helvetica','arial'))) ||
					in_array($v, array('sjis','uhc','big5','gb'))) {
					$newprop[$k] = $v;
					$found = 1;
					break;
				}
			}
			if (!$found) {
			   foreach($aux_fontlist AS $f) {
				$fonttype = trim($f);
				$fonttype = preg_replace('/["\']*(.*?)["\']*/','\\1',$fonttype);
				$fonttype = preg_replace('/ /','',$fonttype);
				$v = strtolower(trim($fonttype));
				if (isset($this->fonttrans[$v]) && $this->fonttrans[$v]) { $v = $this->fonttrans[$v]; }
				if (in_array($v,$this->sans_fonts) || in_array($v,$this->serif_fonts) || in_array($v,$this->mono_fonts) ) {
					$newprop[$k] = $v;
					break;
				}
			   }
			}
		}
		else if ($k == 'MARGIN') {
			$tmp =  $this->expand24($v);
			$newprop['MARGIN-TOP'] = $tmp['T'];
			$newprop['MARGIN-RIGHT'] = $tmp['R'];
			$newprop['MARGIN-BOTTOM'] = $tmp['B'];
			$newprop['MARGIN-LEFT'] = $tmp['L'];
		}
		else if ($k == 'PADDING') {
			$tmp =  $this->expand24($v);
			$newprop['PADDING-TOP'] = $tmp['T'];
			$newprop['PADDING-RIGHT'] = $tmp['R'];
			$newprop['PADDING-BOTTOM'] = $tmp['B'];
			$newprop['PADDING-LEFT'] = $tmp['L'];
		}
		else if ($k == 'BORDER') {
			if ($v == '1') { $v = '1px solid #000000'; }
			else { $v = $this->_fix_borderStr($v); }
			$newprop['BORDER-TOP'] = $v;
			$newprop['BORDER-RIGHT'] = $v;
			$newprop['BORDER-BOTTOM'] = $v;
			$newprop['BORDER-LEFT'] = $v;
		}
		else if ($k == 'BORDER-TOP') {
			$newprop['BORDER-TOP'] = $this->_fix_borderStr($v);
		}
		else if ($k == 'BORDER-RIGHT') {
			$newprop['BORDER-RIGHT'] = $this->_fix_borderStr($v);
		}
		else if ($k == 'BORDER-BOTTOM') {
			$newprop['BORDER-BOTTOM'] = $this->_fix_borderStr($v);
		}
		else if ($k == 'BORDER-LEFT') {
			$newprop['BORDER-LEFT'] = $this->_fix_borderStr($v);
		}
		else if ($k == 'BORDER-STYLE') {
			$e = $this->expand24($v);
			$newprop['BORDER-TOP-STYLE'] = $e['T'];
			$newprop['BORDER-RIGHT-STYLE'] = $e['R'];
			$newprop['BORDER-BOTTOM-STYLE'] = $e['B'];
			$newprop['BORDER-LEFT-STYLE'] = $e['L'];
		}
		else if ($k == 'BORDER-WIDTH') {
			$e = $this->expand24($v);
			$newprop['BORDER-TOP-WIDTH'] = $e['T'];
			$newprop['BORDER-RIGHT-WIDTH'] = $e['R'];
			$newprop['BORDER-BOTTOM-WIDTH'] = $e['B'];
			$newprop['BORDER-LEFT-WIDTH'] = $e['L'];
		}
		else if ($k == 'BORDER-COLOR') {
			$e = $this->expand24($v);
			$newprop['BORDER-TOP-COLOR'] = $e['T'];
			$newprop['BORDER-RIGHT-COLOR'] = $e['R'];
			$newprop['BORDER-BOTTOM-COLOR'] = $e['B'];
			$newprop['BORDER-LEFT-COLOR'] = $e['L'];
		}

		else if ($k == 'BORDER-SPACING') {
			$prop = preg_split('/\s+/',trim($v));
			if (count($prop) == 1 ) {
				$newprop['BORDER-SPACING-H'] = $prop[0];
				$newprop['BORDER-SPACING-V'] = $prop[0];
			}
			else if (count($prop) == 2 ) {
				$newprop['BORDER-SPACING-H'] = $prop[0];
				$newprop['BORDER-SPACING-V'] = $prop[1];
			}
		}
		else if ($k == 'SIZE') {
			$prop = preg_split('/\s+/',trim($v));
			if (preg_match('/(auto|portrait|landscape)/',$prop[0])) {
				$newprop['SIZE'] = strtoupper($prop[0]);
			}
			else if (count($prop) == 1 ) {
				$newprop['SIZE']['W'] = $this->ConvertSize($prop[0]);
				$newprop['SIZE']['H'] = $this->ConvertSize($prop[0]);
			}
			else if (count($prop) == 2 ) {
				$newprop['SIZE']['W'] = $this->ConvertSize($prop[0]);
				$newprop['SIZE']['H'] = $this->ConvertSize($prop[1]);
			}
		}
		else if ($k == 'SHEET-SIZE') {
			$prop = preg_split('/\s+/',trim($v));
			if (count($prop) == 2 ) {
				$newprop['SHEET-SIZE'] = array($this->ConvertSize($prop[0]), $this->ConvertSize($prop[1]));
			}
			else {
				if(preg_match('/([0-9a-zA-Z]*)-L/i',$v,$m)) {	// e.g. A4-L = A$ landscape
					$ft = $this->_getPageFormat($m[1]);
					$format = array($ft[1],$ft[0]);
				}
				else { $format = $this->_getPageFormat($v); }
				if ($format) { $newprop['SHEET-SIZE'] = array($format[0]/_MPDFK, $format[1]/_MPDFK); }
			}
		}
		else if ($k == 'BACKGROUND') {
			$bg = $this->parseCSSbackground($v);
			if ($bg['c']) { $newprop['BACKGROUND-COLOR'] = $bg['c']; }
			else { $newprop['BACKGROUND-COLOR'] = 'transparent'; }
		}
		else if ($k == 'IMAGE-ORIENTATION') {
			if (preg_match('/([\-]*[0-9\.]+)(deg|grad|rad)/i',$v,$m)) {
				$angle = $m[1] + 0;
				if (strtolower($m[2])=='deg') { $angle = $angle; }
				else if (strtolower($m[2])=='grad') { $angle *= (360/400); }
				else if (strtolower($m[2])=='rad') { $angle = rad2deg($angle); }
				while($angle < 0) { $angle += 360; }
				$angle = ($angle % 360);
				$angle /= 90;
				$angle = round($angle) * 90;
				$newprop['IMAGE-ORIENTATION'] = $angle;
			}
		}
		else {
			$newprop[$k] = $v;
		}
	}

	return $newprop;
}

function setCSSboxshadow($v) {
	$sh = array();
	$c = preg_match_all('/(rgba|rgb|cmyka|cmyk|hsla|hsl)\(.*?\)/',$v,$x);
	for($i=0; $i<$c; $i++) {
		$col = preg_replace('/,/','*',$x[0][$i]);
		$v = preg_replace('/'.preg_quote($x[0][$i],'/').'/',$col,$v);
	}
	$ss = explode(',',$v);
	foreach ($ss AS $s) {
		$new = array('inset'=>false, 'blur'=>0, 'spread'=>0);
		if (preg_match('/inset/i',$s)) { $new['inset'] = true; $s = preg_replace('/\s*inset\s*/','',$s); }
		$p = explode(' ',trim($s));
		if (isset($p[0])) { $new['x'] = $this->ConvertSize(trim($p[0]),$this->blk[$this->blklvl-1]['inner_width'],$this->FontSize,false); }
		if (isset($p[1])) { $new['y'] = $this->ConvertSize(trim($p[1]),$this->blk[$this->blklvl-1]['inner_width'],$this->FontSize,false); }
		if (isset($p[2])) {
			if (preg_match('/^\s*[\.\-0-9]/',$p[2])) {
				$new['blur'] = $this->ConvertSize(trim($p[2]),$this->blk[$this->blklvl-1]['inner_width'],$this->FontSize,false);
			}
			else { $new['col'] = $this->ConvertColor(preg_replace('/\*/',',',$p[2])); }
			if (isset($p[3])) {
				if (preg_match('/^\s*[\.\-0-9]/',$p[3])) {
					$new['spread'] = $this->ConvertSize(trim($p[3]),$this->blk[$this->blklvl-1]['inner_width'],$this->FontSize,false);
				}
				else { $new['col'] = $this->ConvertColor(preg_replace('/\*/',',',$p[3])); }
				if (isset($p[4])) {
					$new['col'] = $this->ConvertColor(preg_replace('/\*/',',',$p[4]));
				}
			}
		}
		if (!$new['col']) { $new['col'] = $this->ConvertColor('#888888'); }
		if (isset($new['y'])) { array_unshift($sh, $new); }
	}
	return $sh;
}

function setCSStextshadow($v) {
	$sh = array();
	$c = preg_match_all('/(rgba|rgb|cmyka|cmyk|hsla|hsl)\(.*?\)/',$v,$x);
	for($i=0; $i<$c; $i++) {
		$col = preg_replace('/,/','*',$x[0][$i]);
		$v = preg_replace('/'.preg_quote($x[0][$i],'/').'/',$col,$v);
	}
	$ss = explode(',',$v);
	foreach ($ss AS $s) {
		$new = array('blur'=>0);
		$p = explode(' ',trim($s));
		if (isset($p[0])) { $new['x'] = $this->ConvertSize(trim($p[0]),$this->blk[$this->blklvl-1]['inner_width'],$this->FontSize,false); }
		if (isset($p[1])) { $new['y'] = $this->ConvertSize(trim($p[1]),$this->blk[$this->blklvl-1]['inner_width'],$this->FontSize,false); }
		if (isset($p[2])) {
			if (preg_match('/^\s*[\.\-0-9]/',$p[2])) {
				$new['blur'] = $this->ConvertSize(trim($p[2]),$this->blk[$this->blklvl-1]['inner_width'],$this->FontSize,false);
			}
			else { $new['col'] = $this->ConvertColor(preg_replace('/\*/',',',$p[2])); }
			if (isset($p[3])) {
				$new['col'] = $this->ConvertColor(preg_replace('/\*/',',',$p[3]));
			}
		}
		if (!$new['col']) { $new['col'] = $this->ConvertColor('#888888'); }
		if (isset($new['y'])) { array_unshift($sh, $new); }
	}
	return $sh;
}

function parseCSSbackground($s) {
	$bg = array('c'=>false, 'i'=>false, 'r'=>false, 'p'=>false, );
	if (preg_match('/url\(/i',$s)) {
		// If color, set and strip it off
		if (preg_match('/^\s*(#[0-9a-fA-F]{3,6}|(rgba|rgb|cmyka|cmyk|hsla|hsl|spot)\(.*?\)|[a-zA-Z]{3,})\s+(url\(.*)/i',$s,$m)) {
			$bg['c'] = strtolower($m[1]);
			$s = $m[3];
		}
	}
	else if (preg_match('/^\s*(#[0-9a-fA-F]{3,6}|(rgba|rgb|cmyka|cmyk|hsla|hsl|spot)\(.*?\)|[a-zA-Z]{3,})/i',$s,$m)) { $bg['c'] = strtolower($m[1]); }
	return ($bg);
}


function expand24($mp) {
	$prop = preg_split('/\s+/',trim($mp));
	if (count($prop) == 1 ) {
		return array('T' => $prop[0], 'R' => $prop[0], 'B' => $prop[0], 'L'=> $prop[0]);
	}
	if (count($prop) == 2 ) {
		return array('T' => $prop[0], 'R' => $prop[1], 'B' => $prop[0], 'L'=> $prop[1]);
	}

	if (count($prop) == 3 ) {
		return array('T' => $prop[0], 'R' => $prop[1], 'B' => $prop[2], 'L'=> $prop[1]);
	}
	if (count($prop) == 4 ) {
		return array('T' => $prop[0], 'R' => $prop[1], 'B' => $prop[2], 'L'=> $prop[3]);
	}
	return array();
}





// Return either a number (factor) - based on current set fontsize (if % or em) - or exact lineheight (with 'mm' after it)
function fixLineheight($v) {
	$lh = false;
	if (preg_match('/^[0-9\.,]*$/',$v) && $v >= 0) { return ($v + 0); }
	else if (strtoupper($v) == 'NORMAL') {
		return $this->normalLineheight;
	}
	else {
		$tlh = $this->ConvertSize($v,$this->FontSize,$this->FontSize,true);
		if ($tlh) { return ($tlh.'mm'); }
	}
	return $this->normalLineheight;
}




function setBorderDominance($prop, $val) {
	if (isset($prop['BORDER-LEFT']) && $prop['BORDER-LEFT']) { $this->cell_border_dominance_L = $val; }
	if (isset($prop['BORDER-RIGHT']) && $prop['BORDER-RIGHT']) { $this->cell_border_dominance_R = $val; }
	if (isset($prop['BORDER-TOP']) && $prop['BORDER-TOP']) { $this->cell_border_dominance_T = $val; }
	if (isset($prop['BORDER-BOTTOM']) && $prop['BORDER-BOTTOM']) { $this->cell_border_dominance_B = $val; }
}


function _mergeCSS($p, &$t) {
	// Save Cascading CSS e.g. "div.topic p" at this block level
	if (isset($p) && $p) {
		if ($t) {
			$t = $this->array_merge_recursive_unique($t, $p);
		}
	   	else { $t = $p; }
	}
}

// for CSS handling
function array_merge_recursive_unique($array1, $array2) {
    $arrays = func_get_args();
    $narrays = count($arrays);
    $ret = $arrays[0];
    for ($i = 1; $i < $narrays; $i ++) {
        foreach ($arrays[$i] as $key => $value) {
            if (((string) $key) === ((string) intval($key))) { // integer or string as integer key - append
                $ret[] = $value;
            }
            else { // string key - merge
                if (is_array($value) && isset($ret[$key])) {
                    $ret[$key] = $this->array_merge_recursive_unique($ret[$key], $value);
                }
                else {
                    $ret[$key] = $value;
                }
            }
        }
    }
    return $ret;
}



function _mergeFullCSS($p, &$t, $tag, $classes, $id) {
		$this->_mergeCSS($p[$tag], $t);
		// STYLESHEET CLASS e.g. .smallone{}  .redletter{}
		foreach($classes AS $class) {
		  $this->_mergeCSS($p['CLASS>>'.$class], $t);
		}
		// STYLESHEET nth-child SELECTOR e.g. tr:nth-child(odd)  td:nth-child(2n+1)
		if ($tag=='TR' && isset($p) && $p)  {
			foreach($p AS $k=>$val) {
				if (preg_match('/'.$tag.'>>SELECTORNTHCHILD>>(.*)/',$k, $m)) {
					$select = false;
					if ($tag=='TR')  {
						$row = $this->row;
						$thnr = (isset($this->table[$this->tableLevel][$this->tbctr[$this->tableLevel]]['is_thead']) ? count($this->table[$this->tableLevel][$this->tbctr[$this->tableLevel]]['is_thead']) : 0);
						$tfnr = (isset($this->table[$this->tableLevel][$this->tbctr[$this->tableLevel]]['is_tfoot']) ? count($this->table[$this->tableLevel][$this->tbctr[$this->tableLevel]]['is_tfoot']) : 0);
						if ($this->tabletfoot) { $row -= $thnr; }
						else if (!$this->tablethead) { $row -= ($thnr + $tfnr); }
						if ($m[1]=='ODD' && ($row % 2) == 0) { $select = true; }
						else if ($m[1]=='EVEN' && ($row % 2) == 1) { $select = true; }
						else if (preg_match('/(\d+)N\+(\d+)/',$m[1],$a)) {
							if ((($row + 1) % $a[1]) == $a[2]) { $select = true; }
						}
					}
					else if ($tag=='TD' || $tag=='TH')  {
						if ($m[1]=='ODD' && ($this->col % 2) == 0) { $select = true; }
						else if ($m[1]=='EVEN' && ($this->col % 2) == 1) { $select = true; }
						else if (preg_match('/(\d+)N\+(\d+)/',$m[1],$a)) {
							if ((($this->col + 1) % $a[1]) == $a[2]) { $select = true; }
						}
					}
					if ($select) {
		  				$this->_mergeCSS($p[$tag.'>>SELECTORNTHCHILD>>'.$m[1]], $t);
					}
				}
			}
		}
		// STYLESHEET CLASS e.g. #smallone{}  #redletter{}
		if (isset($id) && $id) {
		  $this->_mergeCSS($p['ID>>'.$id], $t);
		}
		// STYLESHEET CLASS e.g. .smallone{}  .redletter{}
		foreach($classes AS $class) {
		  $this->_mergeCSS($p[$tag.'>>CLASS>>'.$class], $t);
		}
		// STYLESHEET CLASS e.g. #smallone{}  #redletter{}
		if (isset($id)) {
		  $this->_mergeCSS($p[$tag.'>>ID>>'.$id], $t);
		}
}


function _set_mergedCSS(&$m, &$p, $d=true, $bd=false) {
	if (isset($m)) {
		if ((isset($m['depth']) && $m['depth']>1) || $d==false) { 	// include check for 'depth'
			if ($bd) { $this->setBorderDominance($m, $bd); }	// *TABLES*
			if (is_array($m)) {
				$p = array_merge($p,$m);
				$this->_mergeBorders($p,$m);
			}
		}
	}
}


function _mergeBorders(&$b, &$a) {	// Merges $a['BORDER-TOP-STYLE'] to $b['BORDER-TOP'] etc.
  foreach(array('TOP','RIGHT','BOTTOM','LEFT') AS $side) {
    foreach(array('STYLE','WIDTH','COLOR') AS $el) {
	if (isset($a['BORDER-'.$side.'-'.$el])) {	// e.g. $b['BORDER-TOP-STYLE']
		$s = trim($a['BORDER-'.$side.'-'.$el]);
		if (isset($b['BORDER-'.$side])) {	// e.g. $b['BORDER-TOP']
			$p = trim($b['BORDER-'.$side]);
		}
		else { $p = ''; }
		if ($el=='STYLE') {
			if ($p) { $b['BORDER-'.$side] = preg_replace('/(\S+)\s+(\S+)\s+(\S+)/', '\\1 '.$s.' \\3', $p); }
			else { $b['BORDER-'.$side] = '0px '.$s.' #000000'; }
		}
		else if ($el=='WIDTH') {
			if ($p) { $b['BORDER-'.$side] = preg_replace('/(\S+)\s+(\S+)\s+(\S+)/', $s.' \\2 \\3', $p); }
			else { $b['BORDER-'.$side] = $s.' none #000000'; }
		}
		else if ($el=='COLOR') {
			if ($p) { $b['BORDER-'.$side] = preg_replace('/(\S+)\s+(\S+)\s+(\S+)/', '\\1 \\2 '.$s, $p); }
			else { $b['BORDER-'.$side] = '0px none '.$s; }
		}
	}
    }
  }
}


function MergeCSS($inherit,$tag,$attr) {
	$p = array();
	$zp = array();

	$classes = array();
	if (isset($attr['CLASS'])) {
		$classes = preg_split('/\s+/',$attr['CLASS']);
	}
	if (!isset($attr['ID'])) { $attr['ID']=''; }
	//===============================================
	// Set Inherited properties
	if ($inherit == 'TOPTABLE') {	// $tag = TABLE
		//===============================================
		// Save Cascading CSS e.g. "div.topic p" at this block level

		if (isset($this->blk[$this->blklvl]['cascadeCSS'])) {
			$this->tablecascadeCSS[0] = $this->blk[$this->blklvl]['cascadeCSS'];
		}
		else {
			$this->tablecascadeCSS[0] = $this->cascadeCSS;
		}
	}
	//===============================================
	// Set Inherited properties
	if ($inherit == 'TOPTABLE' || $inherit == 'TABLE') {
		//Cascade everything from last level that is not an actual property, or defined by current tag/attributes
		if (isset($this->tablecascadeCSS[$this->tbCSSlvl-1]) && is_array($this->tablecascadeCSS[$this->tbCSSlvl-1])) {
		   foreach($this->tablecascadeCSS[$this->tbCSSlvl-1] AS $k=>$v) {
				$this->tablecascadeCSS[$this->tbCSSlvl][$k] = $v;
		   }
		}
		$this->_mergeFullCSS($this->cascadeCSS, $this->tablecascadeCSS[$this->tbCSSlvl], $tag, $classes, $attr['ID']);
		//===============================================
		// Cascading forward CSS e.g. "table.topic td" for this table in $this->tablecascadeCSS
		//===============================================
		// STYLESHEET TAG e.g. table
		$this->_mergeFullCSS($this->tablecascadeCSS[$this->tbCSSlvl-1], $this->tablecascadeCSS[$this->tbCSSlvl], $tag, $classes, $attr['ID']);
		//===============================================
	}
	//===============================================
	// Set Inherited properties
	if ($inherit == 'TOPLIST') {	// $tag = UL,OL
		//===============================================
		// Save Cascading CSS e.g. "div.topic p" at this block level
		if (isset($this->blk[$this->blklvl]['cascadeCSS'])) {
			$this->listcascadeCSS[0] = $this->blk[$this->blklvl]['cascadeCSS'];
		}
		else {
			$this->listcascadeCSS[0] = $this->cascadeCSS;
		}
	}
	//===============================================
	// Set Inherited properties
	if ($inherit == 'TOPLIST' || $inherit == 'LIST') {
		//Cascade everything from last level that is not an actual property, or defined by current tag/attributes
		if (isset($this->listcascadeCSS[$this->listCSSlvl-1]) && is_array($this->listcascadeCSS[$this->listCSSlvl-1])) {
		   foreach($this->listcascadeCSS[$this->listCSSlvl-1] AS $k=>$v) {
				$this->listcascadeCSS[$this->listCSSlvl][$k] = $v;
		   }
		}
		$this->_mergeFullCSS($this->cascadeCSS, $this->listcascadeCSS[$this->listCSSlvl], $tag, $classes, $attr['ID']);
		//===============================================
		// Cascading forward CSS e.g. "table.topic td" for this list in $this->listcascadeCSS
		//===============================================
		// STYLESHEET TAG e.g. table
		$this->_mergeFullCSS($this->listcascadeCSS[$this->listCSSlvl-1], $this->listcascadeCSS[$this->listCSSlvl], $tag, $classes, $attr['ID']);
		//===============================================
	}
	//===============================================
	// Set Inherited properties
	if ($inherit == 'BLOCK') {
		if (isset($this->blk[$this->blklvl-1]['cascadeCSS']) && is_array($this->blk[$this->blklvl-1]['cascadeCSS'])) {
		   foreach($this->blk[$this->blklvl-1]['cascadeCSS'] AS $k=>$v) {
				$this->blk[$this->blklvl]['cascadeCSS'][$k] = $v;

		   }
		}

		//===============================================
		// Save Cascading CSS e.g. "div.topic p" at this block level
		$this->_mergeFullCSS($this->cascadeCSS, $this->blk[$this->blklvl]['cascadeCSS'], $tag, $classes, $attr['ID']);
		//===============================================
		// Cascading forward CSS
		//===============================================
		$this->_mergeFullCSS($this->blk[$this->blklvl-1]['cascadeCSS'], $this->blk[$this->blklvl]['cascadeCSS'], $tag, $classes, $attr['ID']);
		//===============================================
		  // Block properties
		  if (isset($this->blk[$this->blklvl-1]['margin_collapse']) && $this->blk[$this->blklvl-1]['margin_collapse']) { $p['MARGIN-COLLAPSE'] = 'COLLAPSE'; }	// custom tag, but follows CSS principle that border-collapse is inherited
		  if (isset($this->blk[$this->blklvl-1]['line_height']) && $this->blk[$this->blklvl-1]['line_height']) { $p['LINE-HEIGHT'] = $this->blk[$this->blklvl-1]['line_height']; }

		  if (isset($this->blk[$this->blklvl-1]['direction']) && $this->blk[$this->blklvl-1]['direction']) { $p['DIRECTION'] = $this->blk[$this->blklvl-1]['direction']; }

		  if (isset($this->blk[$this->blklvl-1]['align']) && $this->blk[$this->blklvl-1]['align']) {
			if ($this->blk[$this->blklvl-1]['align'] == 'L') { $p['TEXT-ALIGN'] = 'left'; }
			else if ($this->blk[$this->blklvl-1]['align'] == 'J') { $p['TEXT-ALIGN'] = 'justify'; }
			else if ($this->blk[$this->blklvl-1]['align'] == 'R') { $p['TEXT-ALIGN'] = 'right'; }
			else if ($this->blk[$this->blklvl-1]['align'] == 'C') { $p['TEXT-ALIGN'] = 'center'; }
		  }
		  if ($this->ColActive || $this->keep_block_together) {
		  	if (isset($this->blk[$this->blklvl-1]['bgcolor']) && $this->blk[$this->blklvl-1]['bgcolor']) { // Doesn't officially inherit, but default value is transparent (?=inherited)
				$cor = $this->blk[$this->blklvl-1]['bgcolorarray' ];
				$p['BACKGROUND-COLOR'] = $this->_colAtoString($cor);
			}
		  }

		if (isset($this->blk[$this->blklvl-1]['text_indent']) && ($this->blk[$this->blklvl-1]['text_indent'] || $this->blk[$this->blklvl-1]['text_indent']===0)) { $p['TEXT-INDENT'] = $this->blk[$this->blklvl-1]['text_indent']; }
		if (isset($this->blk[$this->blklvl-1]['InlineProperties'])) {
			$biilp = $this->blk[$this->blklvl-1]['InlineProperties'];
		}
		else { $biilp = null; }
		if (isset($biilp[ 'family' ]) && $biilp[ 'family' ]) { $p['FONT-FAMILY'] = $biilp[ 'family' ]; }
		if (isset($biilp[ 'I' ]) && $biilp[ 'I' ]) { $p['FONT-STYLE'] = 'italic'; }
		if (isset($biilp[ 'sizePt' ]) && $biilp[ 'sizePt' ]) { $p['FONT-SIZE'] = $biilp[ 'sizePt' ] . 'pt'; }
		if (isset($biilp[ 'B' ]) && $biilp[ 'B' ]) { $p['FONT-WEIGHT'] = 'bold'; }
		if (isset($biilp[ 'colorarray' ]) && $biilp[ 'colorarray' ]) {
			$cor = $biilp[ 'colorarray' ];
			$p['COLOR'] = $this->_colAtoString($cor);
		}
		if (isset($biilp[ 'fontkerning' ])) {
			if ($biilp[ 'fontkerning' ]) { $p['FONT-KERNING'] = 'normal'; }
			else { $p['FONT-KERNING'] = 'none'; }
		}
		if (isset($biilp[ 'lSpacingCSS' ]) && $biilp[ 'lSpacingCSS' ]) { $p['LETTER-SPACING'] = $biilp[ 'lSpacingCSS' ]; }
		if (isset($biilp[ 'wSpacingCSS' ]) && $biilp[ 'wSpacingCSS' ]) { $p['WORD-SPACING'] = $biilp[ 'wSpacingCSS' ]; }
		if (isset($biilp[ 'toupper' ]) && $biilp[ 'toupper' ]) { $p['TEXT-TRANSFORM'] = 'uppercase'; }
		else if (isset($biilp[ 'tolower' ]) && $biilp[ 'tolower' ]) { $p['TEXT-TRANSFORM'] = 'lowercase'; }
		else if (isset($biilp[ 'capitalize' ]) && $biilp[ 'capitalize' ]) { $p['TEXT-TRANSFORM'] = 'capitalize'; }
			// CSS says text-decoration is not inherited, but IE7 does??
		if (isset($biilp[ 'underline' ]) && $biilp[ 'underline' ]) { $p['TEXT-DECORATION'] = 'underline'; }
		if (isset($biilp[ 'smCaps' ]) && $biilp[ 'smCaps' ]) { $p['FONT-VARIANT'] = 'small-caps'; }

	}
	//===============================================
	//===============================================
	// Set Inherited properties
	if ($inherit == 'TOPLIST') {
		if ($this->listCSSlvl == 1) {
		    $bilp = $this->blk[$this->blklvl]['InlineProperties'];
		    if (isset($bilp[ 'family' ]) && $bilp[ 'family' ]) { $p['FONT-FAMILY'] = $bilp[ 'family' ]; }
   		    if (isset($bilp[ 'I' ]) && $bilp[ 'I' ]) { $p['FONT-STYLE'] = 'italic'; }
   		    if (isset($bilp[ 'sizePt' ]) && $bilp[ 'sizePt' ]) { $p['FONT-SIZE'] = $bilp[ 'sizePt' ] . 'pt'; }
   		    if (isset($bilp[ 'B' ]) && $bilp[ 'B' ]) { $p['FONT-WEIGHT'] = 'bold'; }
   		    if (isset($bilp[ 'colorarray' ]) && $bilp[ 'colorarray' ]) {
			$cor = $bilp[ 'colorarray' ];
			$p['COLOR'] = $this->_colAtoString($cor);
		    }
		    if (isset($bilp[ 'toupper' ]) && $bilp[ 'toupper' ]) { $p['TEXT-TRANSFORM'] = 'uppercase'; }
		    else if (isset($bilp[ 'tolower' ]) && $bilp[ 'tolower' ]) { $p['TEXT-TRANSFORM'] = 'lowercase'; }
		    else if (isset($bilp[ 'capitalize' ]) && $bilp[ 'capitalize' ]) { $p['TEXT-TRANSFORM'] = 'capitalize'; }
		    if (isset($bilp[ 'fontkerning' ])) {
			if ($bilp[ 'fontkerning' ]) { $p['FONT-KERNING'] = 'normal'; }
			else { $p['FONT-KERNING'] = 'none'; }
		    }
		    if (isset($bilp[ 'lSpacingCSS' ]) && $bilp[ 'lSpacingCSS' ]) { $p['LETTER-SPACING'] = $bilp[ 'lSpacingCSS' ]; }
		    if (isset($bilp[ 'wSpacingCSS' ]) && $bilp[ 'wSpacingCSS' ]) { $p['WORD-SPACING'] = $bilp[ 'wSpacingCSS' ]; }
			// CSS says text-decoration is not inherited, but IE7 does??
		    if (isset($bilp[ 'underline' ]) && $bilp[ 'underline' ]) { $p['TEXT-DECORATION'] = 'underline'; }
		    if (isset($bilp[ 'smCaps' ]) && $bilp[ 'smCaps' ]) { $p['FONT-VARIANT'] = 'small-caps'; }
		    if ($tag=='LI') {
			// Note to self - this should never work, as TOPLIST is not called when LI (see code removed in v5.3)
			$this->Error("If you see this message, please report this as a bug to the mPDF Forum.");
		    }
		}
	}
	//===============================================
	//===============================================
	// DEFAULT for this TAG set in DefaultCSS
	if (isset($this->defaultCSS[$tag])) {
			$zp = $this->fixCSS($this->defaultCSS[$tag]);
			if (is_array($zp)) { 	// Default overwrites Inherited
				$p = array_merge($p,$zp); 	// !! Note other way round !!
				$this->_mergeBorders($p,$zp);
			}
	}
	//===============================================
	// cellPadding overwrites TD/TH default but not specific CSS set on cell
	if (($tag=='TD' || $tag=='TH') && isset($this->table[$this->tableLevel][$this->tbctr[$this->tableLevel]]['cell_padding']) && ($this->table[$this->tableLevel][$this->tbctr[$this->tableLevel]]['cell_padding'] || $this->table[$this->tableLevel][$this->tbctr[$this->tableLevel]]['cell_padding']===0)) {
		$p['PADDING-LEFT'] = $this->table[$this->tableLevel][$this->tbctr[$this->tableLevel]]['cell_padding'];
		$p['PADDING-RIGHT'] = $this->table[$this->tableLevel][$this->tbctr[$this->tableLevel]]['cell_padding'];
		$p['PADDING-TOP'] = $this->table[$this->tableLevel][$this->tbctr[$this->tableLevel]]['cell_padding'];
		$p['PADDING-BOTTOM'] = $this->table[$this->tableLevel][$this->tbctr[$this->tableLevel]]['cell_padding'];
	}
	//===============================================
	// STYLESHEET TAG e.g. h1  p  div  table
	if (isset($this->CSS[$tag]) && $this->CSS[$tag]) {
			$zp = $this->CSS[$tag];
			if (is_array($zp)) {
				$p = array_merge($p,$zp);
				$this->_mergeBorders($p,$zp);
			}
	}
	//===============================================
	// STYLESHEET CLASS e.g. .smallone{}  .redletter{}
	foreach($classes AS $class) {
			$zp = array();
			if (isset($this->CSS['CLASS>>'.$class]) && $this->CSS['CLASS>>'.$class]) { $zp = $this->CSS['CLASS>>'.$class]; }
			if (is_array($zp)) {
				$p = array_merge($p,$zp);
				$this->_mergeBorders($p,$zp);
			}
	}
	//===============================================
	// STYLESHEET nth-child SELECTOR e.g. tr:nth-child(odd)  td:nth-child(2n+1)
	if ($tag=='TR' || $tag=='TD' || $tag=='TH')  {
		foreach($this->CSS AS $k=>$val) {
			if (preg_match('/'.$tag.'>>SELECTORNTHCHILD>>(.*)/',$k, $m)) {
				$select = false;
				if ($tag=='TR')  {
					$row = $this->row;
					$thnr = (isset($this->table[$this->tableLevel][$this->tbctr[$this->tableLevel]]['is_thead']) ? count($this->table[$this->tableLevel][$this->tbctr[$this->tableLevel]]['is_thead']) : 0);
					$tfnr = (isset($this->table[$this->tableLevel][$this->tbctr[$this->tableLevel]]['is_tfoot']) ? count($this->table[$this->tableLevel][$this->tbctr[$this->tableLevel]]['is_tfoot']) : 0);
					if ($this->tabletfoot) { $row -= $thnr; }
					else if (!$this->tablethead) { $row -= ($thnr + $tfnr); }
					if ($m[1]=='ODD' && ($row % 2) == 0) { $select = true; }
					else if ($m[1]=='EVEN' && ($row % 2) == 1) { $select = true; }
					else if (preg_match('/(\d+)N\+(\d+)/',$m[1],$a)) {
						if ((($row + 1) % $a[1]) == $a[2]) { $select = true; }
					}
				}
				else  if ($tag=='TD' || $tag=='TH')  {
					if ($m[1]=='ODD' && ($this->col % 2) == 0) { $select = true; }
					else if ($m[1]=='EVEN' && ($this->col % 2) == 1) { $select = true; }
					else if (preg_match('/(\d+)N\+(\d+)/',$m[1],$a)) {
						if ((($this->col+1) % $a[1]) == $a[2]) { $select = true; }
					}
				}
				if ($select) {
					$zp = $this->CSS[$tag.'>>SELECTORNTHCHILD>>'.$m[1]];
					if ($tag=='TD' || $tag=='TH')  { $this->setBorderDominance($zp, 9); }
					if (is_array($zp)) {
						$p = array_merge($p,$zp);
						$this->_mergeBorders($p,$zp);
					}
				}
			}
		}
	}
	//===============================================
	// STYLESHEET ID e.g. #smallone{}  #redletter{}
	if (isset($attr['ID']) && isset($this->CSS['ID>>'.$attr['ID']]) && $this->CSS['ID>>'.$attr['ID']]) {
			$zp = $this->CSS['ID>>'.$attr['ID']];
			if (is_array($zp)) {
				$p = array_merge($p,$zp);
				$this->_mergeBorders($p,$zp);
			}
	}
	//===============================================
	// STYLESHEET CLASS e.g. p.smallone{}  div.redletter{}
	foreach($classes AS $class) {
			$zp = array();
			if (isset($this->CSS[$tag.'>>CLASS>>'.$class]) && $this->CSS[$tag.'>>CLASS>>'.$class]) { $zp = $this->CSS[$tag.'>>CLASS>>'.$class]; }
			if (is_array($zp)) {
				$p = array_merge($p,$zp);
				$this->_mergeBorders($p,$zp);
			}
	}
	//===============================================
	// STYLESHEET CLASS e.g. p#smallone{}  div#redletter{}
	if (isset($attr['ID']) && isset($this->CSS[$tag.'>>ID>>'.$attr['ID']]) && $this->CSS[$tag.'>>ID>>'.$attr['ID']]) {
			$zp = $this->CSS[$tag.'>>ID>>'.$attr['ID']];
			if (is_array($zp)) {
				$p = array_merge($p,$zp);
				$this->_mergeBorders($p,$zp);
			}
	}
	//===============================================
	// Cascaded e.g. div.class p only works for block level
	if ($inherit == 'BLOCK') {
		$this->_set_mergedCSS($this->blk[$this->blklvl-1]['cascadeCSS'][$tag], $p);
		foreach($classes AS $class) {
			$this->_set_mergedCSS($this->blk[$this->blklvl-1]['cascadeCSS']['CLASS>>'.$class], $p);
		}
		$this->_set_mergedCSS($this->blk[$this->blklvl-1]['cascadeCSS']['ID>>'.$attr['ID']], $p);
		foreach($classes AS $class) {
			$this->_set_mergedCSS($this->blk[$this->blklvl-1]['cascadeCSS'][$tag.'>>CLASS>>'.$class], $p);
		}
		$this->_set_mergedCSS($this->blk[$this->blklvl-1]['cascadeCSS'][$tag.'>>ID>>'.$attr['ID']], $p);
	}
	else if ($inherit == 'INLINE') {
		$this->_set_mergedCSS($this->blk[$this->blklvl]['cascadeCSS'][$tag], $p);
		foreach($classes AS $class) {
			$this->_set_mergedCSS($this->blk[$this->blklvl]['cascadeCSS']['CLASS>>'.$class], $p);
		}
		$this->_set_mergedCSS($this->blk[$this->blklvl]['cascadeCSS']['ID>>'.$attr['ID']], $p);
		foreach($classes AS $class) {
			$this->_set_mergedCSS($this->blk[$this->blklvl]['cascadeCSS'][$tag.'>>CLASS>>'.$class], $p);
		}
		$this->_set_mergedCSS($this->blk[$this->blklvl]['cascadeCSS'][$tag.'>>ID>>'.$attr['ID']], $p);
	}
	else if ($inherit == 'TOPTABLE' || $inherit == 'TABLE') { // NB looks at $this->tablecascadeCSS-1 for cascading CSS
		// false, 9 = don't check for 'depth' and do set border dominance
		$this->_set_mergedCSS($this->tablecascadeCSS[$this->tbCSSlvl-1][$tag], $p, false, 9);
		foreach($classes AS $class) {
			$this->_set_mergedCSS($this->tablecascadeCSS[$this->tbCSSlvl-1]['CLASS>>'.$class], $p, false, 9);
		}
		// STYLESHEET nth-child SELECTOR e.g. tr:nth-child(odd)  td:nth-child(2n+1)
		if ($tag=='TR' || $tag=='TD' || $tag=='TH')  {
			foreach($this->tablecascadeCSS[$this->tbCSSlvl-1] AS $k=>$val) {
				if (preg_match('/'.$tag.'>>SELECTORNTHCHILD>>(.*)/',$k, $m)) {
					$select = false;
					if ($tag=='TR')  {
						$row = $this->row;
						$thnr = (isset($this->table[$this->tableLevel][$this->tbctr[$this->tableLevel]]['is_thead']) ? count($this->table[$this->tableLevel][$this->tbctr[$this->tableLevel]]['is_thead']) : 0);
						$tfnr = (isset($this->table[$this->tableLevel][$this->tbctr[$this->tableLevel]]['is_tfoot']) ? count($this->table[$this->tableLevel][$this->tbctr[$this->tableLevel]]['is_tfoot']) : 0);
						if ($this->tabletfoot) { $row -= $thnr; }
						else if (!$this->tablethead) { $row -= ($thnr + $tfnr); }
						if ($m[1]=='ODD' && ($row % 2) == 0) { $select = true; }
						else if ($m[1]=='EVEN' && ($row % 2) == 1) { $select = true; }
						else if (preg_match('/(\d+)N\+(\d+)/',$m[1],$a)) {
							if ((($row + 1) % $a[1]) == $a[2]) { $select = true; }
						}
					}
					else if ($tag=='TD' || $tag=='TH')  {
						if ($m[1]=='ODD' && ($this->col % 2) == 0) { $select = true; }
						else if ($m[1]=='EVEN' && ($this->col % 2) == 1) { $select = true; }
						else if (preg_match('/(\d+)N\+(\d+)/',$m[1],$a)) {
							if ((($this->col + 1) % $a[1]) == $a[2]) { $select = true; }
						}
					}
					if ($select) {
						$this->_set_mergedCSS($this->tablecascadeCSS[$this->tbCSSlvl-1][$tag.'>>SELECTORNTHCHILD>>'.$m[1]], $p, false, 9);
					}
				}
			}
		}
		$this->_set_mergedCSS($this->tablecascadeCSS[$this->tbCSSlvl-1]['ID>>'.$attr['ID']], $p, false, 9);
		foreach($classes AS $class) {
			$this->_set_mergedCSS($this->tablecascadeCSS[$this->tbCSSlvl-1][$tag.'>>CLASS>>'.$class], $p, false, 9);
		}
		$this->_set_mergedCSS($this->tablecascadeCSS[$this->tbCSSlvl-1][$tag.'>>ID>>'.$attr['ID']], $p, false, 9);
	}
	//===============================================
	else if ($inherit == 'TOPLIST' || $inherit == 'LIST') { // NB looks at $this->listcascadeCSS-1 for cascading CSS
		// false = don't check for 'depth'
		$this->_set_mergedCSS($this->listcascadeCSS[$this->listCSSlvl-1][$tag], $p, false);
		foreach($classes AS $class) {
			$this->_set_mergedCSS($this->listcascadeCSS[$this->listCSSlvl-1]['CLASS>>'.$class], $p, false);
		}
		$this->_set_mergedCSS($this->listcascadeCSS[$this->listCSSlvl-1]['ID>>'.$attr['ID']], $p, false);
		foreach($classes AS $class) {
			$this->_set_mergedCSS($this->listcascadeCSS[$this->listCSSlvl-1][$tag.'>>CLASS>>'.$class], $p, false);
		}
		$this->_set_mergedCSS($this->listcascadeCSS[$this->listCSSlvl-1][$tag.'>>ID>>'.$attr['ID']], $p, false);
	}
	//===============================================
	//===============================================
	// INLINE STYLE e.g. style="CSS:property"
	if (isset($attr['STYLE'])) {
			$zp = $this->readInlineCSS($attr['STYLE']);
			if (is_array($zp)) {
				$p = array_merge($p,$zp);
				$this->_mergeBorders($p,$zp);
			}
	}
	//===============================================
	//===============================================
	// INLINE ATTRIBUTES e.g. .. ALIGN="CENTER">
	if (isset($attr['LANG']) and $attr['LANG']!='') {
			$p['LANG'] = $attr['LANG'];
	}
	if (isset($attr['COLOR']) and $attr['COLOR']!='') {
			$p['COLOR'] = $attr['COLOR'];
	}
	if ($tag != 'INPUT') {
		if (isset($attr['WIDTH']) and $attr['WIDTH']!='') {
			$p['WIDTH'] = $attr['WIDTH'];
		}
		if (isset($attr['HEIGHT']) and $attr['HEIGHT']!='') {
			$p['HEIGHT'] = $attr['HEIGHT'];
		}
	}
	if ($tag == 'FONT') {
		if (isset($attr['FACE'])) {
			$p['FONT-FAMILY'] = $attr['FACE'];
		}
		if (isset($attr['SIZE']) and $attr['SIZE']!='') {
			$s = '';
			if ($attr['SIZE'] === '+1') { $s = '120%'; }
			else if ($attr['SIZE'] === '-1') { $s = '86%'; }
			else if ($attr['SIZE'] === '1') { $s = 'XX-SMALL'; }
			else if ($attr['SIZE'] == '2') { $s = 'X-SMALL'; }
			else if ($attr['SIZE'] == '3') { $s = 'SMALL'; }
			else if ($attr['SIZE'] == '4') { $s = 'MEDIUM'; }
			else if ($attr['SIZE'] == '5') { $s = 'LARGE'; }
			else if ($attr['SIZE'] == '6') { $s = 'X-LARGE'; }
			else if ($attr['SIZE'] == '7') { $s = 'XX-LARGE'; }
			if ($s) $p['FONT-SIZE'] = $s;
		}
	}
	if (isset($attr['VALIGN']) and $attr['VALIGN']!='') {
		$p['VERTICAL-ALIGN'] = $attr['VALIGN'];
	}
	if (isset($attr['VSPACE']) and $attr['VSPACE']!='') {
		$p['MARGIN-TOP'] = $attr['VSPACE'];
		$p['MARGIN-BOTTOM'] = $attr['VSPACE'];
	}
	if (isset($attr['HSPACE']) and $attr['HSPACE']!='') {
		$p['MARGIN-LEFT'] = $attr['HSPACE'];
		$p['MARGIN-RIGHT'] = $attr['HSPACE'];
	}
	//===============================================
	return $p;
}



function PreviewBlockCSS($tag,$attr) {
	// Looks ahead from current block level to a new level
	$p = array();
	$zp = array();
	$oldcascadeCSS = $this->blk[$this->blklvl]['cascadeCSS'];
	$classes = array();
	if (isset($attr['CLASS'])) { $classes = preg_split('/\s+/',$attr['CLASS']); }
	//===============================================
	// DEFAULT for this TAG set in DefaultCSS
	if (isset($this->defaultCSS[$tag])) {
		$zp = $this->fixCSS($this->defaultCSS[$tag]);
		if (is_array($zp)) { $p = array_merge($zp,$p); }	// Inherited overwrites default
	}
	// STYLESHEET TAG e.g. h1  p  div  table
	if (isset($this->CSS[$tag])) {
		$zp = $this->CSS[$tag];
		if (is_array($zp)) { $p = array_merge($p,$zp); }
	}
	// STYLESHEET CLASS e.g. .smallone{}  .redletter{}
	foreach($classes AS $class) {
		$zp = array();
		if (isset($this->CSS['CLASS>>'.$class])) { $zp = $this->CSS['CLASS>>'.$class]; }
		if (is_array($zp)) { $p = array_merge($p,$zp); }
	}
	// STYLESHEET ID e.g. #smallone{}  #redletter{}
	if (isset($attr['ID']) && isset($this->CSS['ID>>'.$attr['ID']])) {
		$zp = $this->CSS['ID>>'.$attr['ID']];
		if (is_array($zp)) { $p = array_merge($p,$zp); }
	}
	// STYLESHEET CLASS e.g. p.smallone{}  div.redletter{}
	foreach($classes AS $class) {
		$zp = array();
		if (isset($this->CSS[$tag.'>>CLASS>>'.$class])) { $zp = $this->CSS[$tag.'>>CLASS>>'.$class]; }
		if (is_array($zp)) { $p = array_merge($p,$zp); }
	}
	// STYLESHEET CLASS e.g. p#smallone{}  div#redletter{}
	if (isset($attr['ID']) && isset($this->CSS[$tag.'>>ID>>'.$attr['ID']])) {
		$zp = $this->CSS[$tag.'>>ID>>'.$attr['ID']];
		if (is_array($zp)) { $p = array_merge($p,$zp); }
	}
	//===============================================
	// STYLESHEET TAG e.g. div h1    div p

	$this->_set_mergedCSS($oldcascadeCSS[$tag], $p);
	// STYLESHEET CLASS e.g. .smallone{}  .redletter{}
	foreach($classes AS $class) {
	  
	  $this->_set_mergedCSS($oldcascadeCSS['CLASS>>'.$class], $p);
	}
	// STYLESHEET CLASS e.g. #smallone{}  #redletter{}
	if (isset($attr['ID'])) {
	  
	  $this->_set_mergedCSS($oldcascadeCSS['ID>>'.$attr['ID']], $p);
	}
	// STYLESHEET CLASS e.g. div.smallone{}  p.redletter{}
	foreach($classes AS $class) {
	  
	  $this->_set_mergedCSS($oldcascadeCSS[$tag.'>>CLASS>>'.$class], $p);
	}
	// STYLESHEET CLASS e.g. div#smallone{}  p#redletter{}
	if (isset($attr['ID'])) {
	  
	  $this->_set_mergedCSS($oldcascadeCSS[$tag.'>>ID>>'.$attr['ID']], $p);
	}
	//===============================================
	// INLINE STYLE e.g. style="CSS:property"
	if (isset($attr['STYLE'])) {
		$zp = $this->readInlineCSS($attr['STYLE']);
		if (is_array($zp)) { $p = array_merge($p,$zp); }
	}
	//===============================================
	return $p;
}







function OpenTag($tag,$attr)
{

  // What this gets: < $tag $attr['WIDTH']="90px" > does not get content here </closeTag here>
  // Correct tags where HTML specifies optional end tags,
  // and/or does not allow nesting e.g. P inside P, or
  if ($this->allow_html_optional_endtags) {
    if (($tag == 'P' || $tag == 'DIV' || $tag == 'H1' || $tag == 'H2' || $tag == 'H3' || $tag == 'H4' || $tag == 'H5' || $tag == 'H6' || $tag == 'UL' || $tag == 'OL' || $tag == 'TABLE' || $tag=='PRE' || $tag=='FORM' || $tag=='ADDRESS' || $tag=='BLOCKQUOTE' || $tag=='CENTER' || $tag=='DL' || $tag == 'HR' ) && $this->lastoptionaltag == 'P') { $this->CloseTag($this->lastoptionaltag ); }
    if ($tag == 'DD' && $this->lastoptionaltag == 'DD') { $this->CloseTag($this->lastoptionaltag ); }
    if ($tag == 'DD' && $this->lastoptionaltag == 'DT') { $this->CloseTag($this->lastoptionaltag ); }
    if ($tag == 'DT' && $this->lastoptionaltag == 'DD') { $this->CloseTag($this->lastoptionaltag ); }
    if ($tag == 'DT' && $this->lastoptionaltag == 'DT') { $this->CloseTag($this->lastoptionaltag ); }
    if ($tag == 'LI' && $this->lastoptionaltag == 'LI') { $this->CloseTag($this->lastoptionaltag ); }
    if (($tag == 'TD' || $tag == 'TH') && $this->lastoptionaltag == 'TD') { $this->CloseTag($this->lastoptionaltag ); }	// *TABLES*
    if (($tag == 'TD' || $tag == 'TH') && $this->lastoptionaltag == 'TH') { $this->CloseTag($this->lastoptionaltag ); }	// *TABLES*
    if ($tag == 'TR' && $this->lastoptionaltag == 'TR') { $this->CloseTag($this->lastoptionaltag ); }	// *TABLES*
    if ($tag == 'TR' && $this->lastoptionaltag == 'TD') { $this->CloseTag($this->lastoptionaltag );  $this->CloseTag('TR'); $this->CloseTag('THEAD'); }	// *TABLES*
    if ($tag == 'TR' && $this->lastoptionaltag == 'TH') { $this->CloseTag($this->lastoptionaltag );  $this->CloseTag('TR'); $this->CloseTag('THEAD'); }	// *TABLES*
    if ($tag == 'OPTION' && $this->lastoptionaltag == 'OPTION') { $this->CloseTag($this->lastoptionaltag ); }
  }

  $align = array('left'=>'L','center'=>'C','right'=>'R','top'=>'T','text-top'=>'TT','middle'=>'M','baseline'=>'BS','bottom'=>'B','text-bottom'=>'TB','justify'=>'J');

  $this->ignorefollowingspaces=false;

  //Opening tag
  switch($tag){

     case 'DOTTAB':
	$objattr = array();
	$objattr['type'] = 'dottab';
	$dots=str_repeat('.', 3)."  ";	// minimum number of dots
	$objattr['width'] = $this->GetStringWidth($dots);
	$objattr['margin_top'] = 0;
	$objattr['margin_bottom'] = 0;
	$objattr['margin_left'] = 0;
	$objattr['margin_right'] = 0;
	$objattr['height'] = 0;
	$objattr['colorarray'] = $this->colorarray;
	$objattr['border_top']['w'] = 0;
	$objattr['border_bottom']['w'] = 0;
	$objattr['border_left']['w'] = 0;
	$objattr['border_right']['w'] = 0;
	$e = "\xbb\xa4\xactype=dottab,objattr=".serialize($objattr)."\xbb\xa4\xac";
	// Output it to buffers
	if ($this->tableLevel) {
		if (!isset($this->cell[$this->row][$this->col]['maxs'])) {
			$this->cell[$this->row][$this->col]['maxs'] = $this->cell[$this->row][$this->col]['s'];
		}
		elseif($this->cell[$this->row][$this->col]['maxs'] < $this->cell[$this->row][$this->col]['s']) {
			$this->cell[$this->row][$this->col]['maxs'] = $this->cell[$this->row][$this->col]['s'];
		}
		$this->cell[$this->row][$this->col]['s'] = 0 ;// reset
		$this->_saveCellTextBuffer($e);
	}
	else {
		$this->_saveTextBuffer($e);
	}	// *TABLES*
	break;

     case 'PAGEHEADER':
     case 'PAGEFOOTER':
	$this->ignorefollowingspaces = true;
	if ($attr['NAME']) { $pname = $attr['NAME']; }
	else { $pname = '_default'; }

		if ($tag=='PAGEHEADER') { $p = &$this->pageheaders[$pname]; }
		else { $p = &$this->pagefooters[$pname]; }

		$p['L']=array();
		$p['C']=array();
		$p['R']=array();
		$p['L']['font-style'] = '';
		$p['C']['font-style'] = '';
		$p['R']['font-style'] = '';

		if (isset($attr['CONTENT-LEFT'])) {
			$p['L']['content'] = $attr['CONTENT-LEFT'];
		}
		if (isset($attr['CONTENT-CENTER'])) {
			$p['C']['content'] = $attr['CONTENT-CENTER'];
		}
		if (isset($attr['CONTENT-RIGHT'])) {
			$p['R']['content'] = $attr['CONTENT-RIGHT'];
		}

		if (isset($attr['HEADER-STYLE']) || isset($attr['FOOTER-STYLE'])) {	// font-family,size,weight,style,color
			if ($tag=='PAGEHEADER') { $properties = $this->readInlineCSS($attr['HEADER-STYLE']); }
			else { $properties = $this->readInlineCSS($attr['FOOTER-STYLE']); }
			if (isset($properties['FONT-FAMILY'])) {
				$p['L']['font-family'] = $properties['FONT-FAMILY'];
				$p['C']['font-family'] = $properties['FONT-FAMILY'];
				$p['R']['font-family'] = $properties['FONT-FAMILY'];
			}
			if (isset($properties['FONT-SIZE'])) {
				$p['L']['font-size'] = $this->ConvertSize($properties['FONT-SIZE']) * _MPDFK;
				$p['C']['font-size'] = $this->ConvertSize($properties['FONT-SIZE']) * _MPDFK;
				$p['R']['font-size'] = $this->ConvertSize($properties['FONT-SIZE']) * _MPDFK;
			}
			if (isset($properties['FONT-WEIGHT']) && $properties['FONT-WEIGHT']=='bold') {
				$p['L']['font-style'] = 'B';
				$p['C']['font-style'] = 'B';
				$p['R']['font-style'] = 'B';
			}
			if (isset($properties['FONT-STYLE']) && $properties['FONT-STYLE']=='italic') {
				$p['L']['font-style'] .= 'I';
				$p['C']['font-style'] .= 'I';
				$p['R']['font-style'] .= 'I';
			}
			if (isset($properties['COLOR'])) {
				$p['L']['color'] = $properties['COLOR'];
				$p['C']['color'] = $properties['COLOR'];
				$p['R']['color'] = $properties['COLOR'];
			}
		}
		if (isset($attr['HEADER-STYLE-LEFT']) || isset($attr['FOOTER-STYLE-LEFT'])) {
			if ($tag=='PAGEHEADER') { $properties = $this->readInlineCSS($attr['HEADER-STYLE-LEFT']); }
			else { $properties = $this->readInlineCSS($attr['FOOTER-STYLE-LEFT']); }
			if (isset($properties['FONT-FAMILY'])) { $p['L']['font-family'] = $properties['FONT-FAMILY']; }
			if (isset($properties['FONT-SIZE'])) { $p['L']['font-size'] = $this->ConvertSize($properties['FONT-SIZE']) * _MPDFK; }
			if (isset($properties['FONT-WEIGHT']) && $properties['FONT-WEIGHT']=='bold') { $p['L']['font-style'] ='B'; }
			if (isset($properties['FONT-STYLE']) && $properties['FONT-STYLE']=='italic') { $p['L']['font-style'] .='I'; }
			if (isset($properties['COLOR'])) { $p['L']['color'] = $properties['COLOR']; }
		}
		if (isset($attr['HEADER-STYLE-CENTER']) || isset($attr['FOOTER-STYLE-CENTER'])) {
			if ($tag=='PAGEHEADER') { $properties = $this->readInlineCSS($attr['HEADER-STYLE-CENTER']); }
			else { $properties = $this->readInlineCSS($attr['FOOTER-STYLE-CENTER']); }
			if (isset($properties['FONT-FAMILY'])) { $p['C']['font-family'] = $properties['FONT-FAMILY']; }
			if (isset($properties['FONT-SIZE'])) { $p['C']['font-size'] = $this->ConvertSize($properties['FONT-SIZE']) * _MPDFK; }
			if (isset($properties['FONT-WEIGHT']) && $properties['FONT-WEIGHT']=='bold') { $p['C']['font-style'] = 'B'; }
			if (isset($properties['FONT-STYLE']) && $properties['FONT-STYLE']=='italic') { $p['C']['font-style'] .= 'I'; }
			if (isset($properties['COLOR'])) { $p['C']['color'] = $properties['COLOR']; }
		}
		if (isset($attr['HEADER-STYLE-RIGHT']) || isset($attr['FOOTER-STYLE-RIGHT'])) {
			if ($tag=='PAGEHEADER') { $properties = $this->readInlineCSS($attr['HEADER-STYLE-RIGHT']); }
			else { $properties = $this->readInlineCSS($attr['FOOTER-STYLE-RIGHT']); }
			if (isset($properties['FONT-FAMILY'])) { $p['R']['font-family'] = $properties['FONT-FAMILY']; }
			if (isset($properties['FONT-SIZE'])) { $p['R']['font-size'] = $this->ConvertSize($properties['FONT-SIZE']) * _MPDFK; }
			if (isset($properties['FONT-WEIGHT']) && $properties['FONT-WEIGHT']=='bold') { $p['R']['font-style'] = 'B'; }
			if (isset($properties['FONT-STYLE']) && $properties['FONT-STYLE']=='italic') { $p['R']['font-style'] .= 'I'; }
			if (isset($properties['COLOR'])) { $p['R']['color'] = $properties['COLOR']; }
		}
		if (isset($attr['LINE']) && $attr['LINE']) {	// 0|1|on|off
			if ($attr['LINE']=='1' || strtoupper($attr['LINE'])=='ON') { $lineset=1; }
			else { $lineset=0; }
			$p['line'] = $lineset;
		}
	break;



     case 'SETPAGEHEADER':
     case 'SETPAGEFOOTER':
	$this->ignorefollowingspaces = true;
	if (isset($attr['NAME']) && $attr['NAME']) { $pname = $attr['NAME']; }
	else { $pname = '_default'; }
	if (isset($attr['PAGE']) && $attr['PAGE']) { 	// O|odd|even|E|ALL|[blank]
		if (strtoupper($attr['PAGE'])=='O' || strtoupper($attr['PAGE'])=='ODD') { $side='odd'; }
		else if (strtoupper($attr['PAGE'])=='E' || strtoupper($attr['PAGE'])=='EVEN') { $side='even'; }
		else if (strtoupper($attr['PAGE'])=='ALL') { $side='both'; }
		else { $side='odd'; }
	}
	else { $side='odd'; }
	if (isset($attr['VALUE']) && $attr['VALUE']) { 	// -1|1|on|off
		if ($attr['VALUE']=='1' || strtoupper($attr['VALUE'])=='ON') { $set=1; }
		else if ($attr['VALUE']=='-1' || strtoupper($attr['VALUE'])=='OFF') { $set=0; }
		else { $set=1; }
	}
	else { $set=1; }
	if ($side=='odd' || $side=='both') {
		if ($set && $tag=='SETPAGEHEADER') { $this->headerDetails['odd'] = $this->pageheaders[$pname]; }
		else if ($set && $tag=='SETPAGEFOOTER') { $this->footerDetails['odd'] = $this->pagefooters[$pname]; }
		else if ($tag=='SETPAGEHEADER') { $this->headerDetails['odd'] = array(); }
		else { $this->footerDetails['odd'] = array(); }
		if (!$this->mirrorMargins || ($this->page)%2!=0) {	// ODD
			if ($tag=='SETPAGEHEADER') { $this->_setAutoHeaderHeight($this->headerDetails['odd'],$this->HTMLHeader); }
			if ($tag=='SETPAGEFOOTER') { $this->_setAutoFooterHeight($this->footerDetails['odd'],$this->HTMLFooter); }
		}
	}
	if ($side=='even' || $side=='both') {
		if ($set && $tag=='SETPAGEHEADER') { $this->headerDetails['even'] = $this->pageheaders[$pname]; }
		else if ($set && $tag=='SETPAGEFOOTER') { $this->footerDetails['even'] = $this->pagefooters[$pname]; }
		else if ($tag=='SETPAGEHEADER') { $this->headerDetails['even'] = array(); }
		else { $this->footerDetails['even'] = array(); }
		if ($this->mirrorMargins && ($this->page)%2==0) {	// EVEN
			if ($tag=='SETPAGEHEADER') { $this->_setAutoHeaderHeight($this->headerDetails['even'],$this->HTMLHeaderE); }
			if ($tag=='SETPAGEFOOTER') { $this->_setAutoFooterHeight($this->footerDetails['even'],$this->HTMLFooterE); }
		}
	}
	if (isset($attr['SHOW-THIS-PAGE']) && $attr['SHOW-THIS-PAGE'] && $tag=='SETPAGEHEADER') {
		$this->Header();
	}
	break;




    case 'PAGE_BREAK': //custom-tag
    case 'PAGEBREAK': //custom-tag
    case 'NEWPAGE': //custom-tag
    case 'FORMFEED': //custom-tag

	$save_blklvl = $this->blklvl;
	$save_blk = $this->blk;
	$save_silp = $this->saveInlineProperties();
	$save_spanlvl = $this->spanlvl;
	$save_ilp = $this->InlineProperties;

	// Close any open block tags
	for ($b= $this->blklvl;$b>0;$b--) { $this->CloseTag($this->blk[$b]['tag']); }
	if(!empty($this->textbuffer))  {	//Output previously buffered content
   	  	$this->printbuffer($this->textbuffer);
        	$this->textbuffer=array();
      }
	$this->ignorefollowingspaces = true;
	$save_cols = false;


	if (isset($attr['SHEET-SIZE']) && $tag != 'FORMFEED' && !$this->restoreBlockPageBreaks) {
		// Convert to same types as accepted in initial mPDF() A4, A4-L, or array(w,h)
		$prop = preg_split('/\s+/',trim($attr['SHEET-SIZE']));
		if (count($prop) == 2 ) {
			$newformat = array($this->ConvertSize($prop[0]), $this->ConvertSize($prop[1]));
		}
		else { $newformat = $attr['SHEET-SIZE']; }
	}
	else { $newformat = ''; }


	$mgr = $mgl = $mgt = $mgb = $mgh = $mgf = '';
	if (isset($attr['MARGIN-RIGHT'])) { $mgr = $this->ConvertSize($attr['MARGIN-RIGHT'],$this->w,$this->FontSize,false); }
	if (isset($attr['MARGIN-LEFT'])) { $mgl = $this->ConvertSize($attr['MARGIN-LEFT'],$this->w,$this->FontSize,false); }
	if (isset($attr['MARGIN-TOP'])) { $mgt = $this->ConvertSize($attr['MARGIN-TOP'],$this->w,$this->FontSize,false); }
	if (isset($attr['MARGIN-BOTTOM'])) { $mgb = $this->ConvertSize($attr['MARGIN-BOTTOM'],$this->w,$this->FontSize,false); }
	if (isset($attr['MARGIN-HEADER'])) { $mgh = $this->ConvertSize($attr['MARGIN-HEADER'],$this->w,$this->FontSize,false); }
	if (isset($attr['MARGIN-FOOTER'])) { $mgf = $this->ConvertSize($attr['MARGIN-FOOTER'],$this->w,$this->FontSize,false); }
	$ohname = $ehname = $ofname = $efname = '';
	if (isset($attr['ODD-HEADER-NAME'])) { $ohname = $attr['ODD-HEADER-NAME']; }
	if (isset($attr['EVEN-HEADER-NAME'])) { $ehname = $attr['EVEN-HEADER-NAME']; }
	if (isset($attr['ODD-FOOTER-NAME'])) { $ofname = $attr['ODD-FOOTER-NAME']; }
	if (isset($attr['EVEN-FOOTER-NAME'])) { $efname = $attr['EVEN-FOOTER-NAME']; }
	$ohvalue = $ehvalue = $ofvalue = $efvalue = 0;
	if (isset($attr['ODD-HEADER-VALUE']) && ($attr['ODD-HEADER-VALUE']=='1' || strtoupper($attr['ODD-HEADER-VALUE'])=='ON')) { $ohvalue = 1; }
	else if (isset($attr['ODD-HEADER-VALUE']) && ($attr['ODD-HEADER-VALUE']=='-1' || strtoupper($attr['ODD-HEADER-VALUE'])=='OFF')) { $ohvalue = -1; }
	if (isset($attr['EVEN-HEADER-VALUE']) && ($attr['EVEN-HEADER-VALUE']=='1' || strtoupper($attr['EVEN-HEADER-VALUE'])=='ON')) { $ehvalue = 1; }
	else if (isset($attr['EVEN-HEADER-VALUE']) && ($attr['EVEN-HEADER-VALUE']=='-1' || strtoupper($attr['EVEN-HEADER-VALUE'])=='OFF')) { $ehvalue = -1; }
	if (isset($attr['ODD-FOOTER-VALUE']) && ($attr['ODD-FOOTER-VALUE']=='1' || strtoupper($attr['ODD-FOOTER-VALUE'])=='ON')) { $ofvalue = 1; }
	else if (isset($attr['ODD-FOOTER-VALUE']) && ($attr['ODD-FOOTER-VALUE']=='-1' || strtoupper($attr['ODD-FOOTER-VALUE'])=='OFF')) { $ofvalue = -1; }
	if (isset($attr['EVEN-FOOTER-VALUE']) && ($attr['EVEN-FOOTER-VALUE']=='1' || strtoupper($attr['EVEN-FOOTER-VALUE'])=='ON')) { $efvalue = 1; }
	else if (isset($attr['EVEN-FOOTER-VALUE']) && ($attr['EVEN-FOOTER-VALUE']=='-1' || strtoupper($attr['EVEN-FOOTER-VALUE'])=='OFF')) { $efvalue = -1; }

	if (isset($attr['ORIENTATION']) && (strtoupper($attr['ORIENTATION'])=='L' || strtoupper($attr['ORIENTATION'])=='LANDSCAPE')) { $orient = 'L'; }
	else if (isset($attr['ORIENTATION']) && (strtoupper($attr['ORIENTATION'])=='P' || strtoupper($attr['ORIENTATION'])=='PORTRAIT')) { $orient = 'P'; }
	else { $orient = $this->CurOrientation; }

	if (isset($attr['PAGE-SELECTOR']) && $attr['PAGE-SELECTOR']) { $pagesel = $attr['PAGE-SELECTOR']; }
	else { $pagesel = ''; }

	$resetpagenum = '';
	$pagenumstyle = '';
	$suppress = '';
	if (isset($attr['RESETPAGENUM'])) { $resetpagenum = $attr['RESETPAGENUM']; }
	if (isset($attr['PAGENUMSTYLE'])) { $pagenumstyle = $attr['PAGENUMSTYLE']; }
	if (isset($attr['SUPPRESS'])) { $suppress = $attr['SUPPRESS']; }

	if ($tag == 'TOCPAGEBREAK') { $type = 'NEXT-ODD'; }
	else if(isset($attr['TYPE'])) { $type = strtoupper($attr['TYPE']); }
	else { $type = ''; }

	if ($type == 'E' || $type == 'EVEN') { $this->AddPage($orient,'E', $resetpagenum, $pagenumstyle, $suppress,$mgl,$mgr,$mgt,$mgb,$mgh,$mgf,$ohname,$ehname,$ofname,$efname,$ohvalue,$ehvalue,$ofvalue,$efvalue,$pagesel,$newformat); }
	else if ($type == 'O' || $type == 'ODD') { $this->AddPage($orient,'O', $resetpagenum, $pagenumstyle, $suppress,$mgl,$mgr,$mgt,$mgb,$mgh,$mgf,$ohname,$ehname,$ofname,$efname,$ohvalue,$ehvalue,$ofvalue,$efvalue,$pagesel,$newformat); }
	else if ($type == 'NEXT-ODD') { $this->AddPage($orient,'NEXT-ODD', $resetpagenum, $pagenumstyle, $suppress,$mgl,$mgr,$mgt,$mgb,$mgh,$mgf,$ohname,$ehname,$ofname,$efname,$ohvalue,$ehvalue,$ofvalue,$efvalue,$pagesel,$newformat); }
	else if ($type == 'NEXT-EVEN') { $this->AddPage($orient,'NEXT-EVEN', $resetpagenum, $pagenumstyle, $suppress,$mgl,$mgr,$mgt,$mgb,$mgh,$mgf,$ohname,$ehname,$ofname,$efname,$ohvalue,$ehvalue,$ofvalue,$efvalue,$pagesel,$newformat); }
	else { $this->AddPage($orient,'', $resetpagenum, $pagenumstyle, $suppress,$mgl,$mgr,$mgt,$mgb,$mgh,$mgf,$ohname,$ehname,$ofname,$efname,$ohvalue,$ehvalue,$ofvalue,$efvalue,$pagesel,$newformat); }


	if (($tag == 'FORMFEED' || $this->restoreBlockPagebreaks) && !$this->tableLevel && !$this->listlvl) {
		$this->blk = $save_blk;
		// Re-open block tags
		$t = $this->blk[0]['tag'];
		$a = $this->blk[0]['attr'];
		$this->blklvl = 0;
		for ($b=0; $b<=$save_blklvl;$b++) {
			$tc = $t;
			$ac = $a;
			$t = $this->blk[$b+1]['tag'];
			$a = $this->blk[$b+1]['attr'];
			unset($this->blk[$b+1]);
			$this->OpenTag($tc,$ac);
		}
		$this->spanlvl = $save_spanlvl;
		$this->InlineProperties = $save_ilp;
		$this->restoreInlineProperties($save_silp);
	}

	break;










    case 'BDO':
	// $this->biDirectional = true;
	break;


    case 'TTZ':
	$this->ttz = true;
	$this->InlineProperties[$tag] = $this->saveInlineProperties();
	$this->setCSS(array('FONT-FAMILY'=>'czapfdingbats','FONT-WEIGHT'=>'normal','FONT-STYLE'=>'normal'),'INLINE');
	break;

    case 'TTS':
	$this->tts = true;
	$this->InlineProperties[$tag] = $this->saveInlineProperties();
	$this->setCSS(array('FONT-FAMILY'=>'csymbol','FONT-WEIGHT'=>'normal','FONT-STYLE'=>'normal'),'INLINE');
	break;

    case 'TTA':
	$this->tta = true;
	$this->InlineProperties[$tag] = $this->saveInlineProperties();

	if (in_array($this->FontFamily,$this->mono_fonts)) {
		$this->setCSS(array('FONT-FAMILY'=>'ccourier'),'INLINE');
	}
	else if (in_array($this->FontFamily,$this->serif_fonts)) {
		$this->setCSS(array('FONT-FAMILY'=>'ctimes'),'INLINE');
	}
	else {
		$this->setCSS(array('FONT-FAMILY'=>'chelvetica'),'INLINE');
	}
	break;



    // INLINE PHRASES OR STYLES
    case 'SUB':
    case 'SUP':
    case 'ACRONYM':
    case 'BIG':
    case 'SMALL':
    case 'INS':
    case 'S':
    case 'STRIKE':
    case 'DEL':
    case 'STRONG':
    case 'CITE':
    case 'Q':
    case 'EM':
    case 'B':
    case 'I':
    case 'U':
    case 'SAMP':
    case 'CODE':
    case 'KBD':
    case 'TT':
    case 'VAR':
    case 'FONT':
    case 'MARK':	// mPDF 5.5.09
    case 'TIME':

    case 'SPAN':

	if ($tag == 'SPAN') {
		$this->spanlvl++;
		$this->InlineProperties['SPAN'][$this->spanlvl] = $this->saveInlineProperties();
	}
	else {
		if (!isset($this->InlineProperties[$tag])) $this->InlineProperties[$tag] = $this->saveInlineProperties(); // mPDF 5.4.13
	}
	$properties = $this->MergeCSS('INLINE',$tag,$attr);
	if (!empty($properties)) $this->setCSS($properties,'INLINE');
	break;


    case 'A':
	if (isset($attr['NAME']) and $attr['NAME'] != '') {
		$e = '';
		if($this->tableLevel) {	// *TABLES*
			$this->_saveCellTextBuffer($e, '', $attr['NAME']);	// *TABLES*
		}	// *TABLES*
		else  {	// *TABLES*
			$this->_saveTextBuffer($e, '', $attr['NAME']);	//an internal link (adds a space for recognition)
		}	// *TABLES*
	}
	if (isset($attr['HREF'])) {
		$this->InlineProperties['A'] = $this->saveInlineProperties();
		$properties = $this->MergeCSS('',$tag,$attr);
		if (!empty($properties)) $this->setCSS($properties,'INLINE');
		$this->HREF=htmlspecialchars_decode(urldecode($attr['HREF']));
	}
	break;

    case 'LEGEND':	// mPDF 5.4.18
		$this->InlineProperties['LEGEND'] = $this->saveInlineProperties();
		$properties = $this->MergeCSS('',$tag,$attr);
		if (!empty($properties)) $this->setCSS($properties,'INLINE');
	break;



    case 'PROGRESS':	// mPDF 5.5.09
    case 'METER':	// mPDF 5.5.09
	$this->inMeter = true;	// mPDF 5.5.09

	if (isset($attr['MAX']) && $attr['MAX']) { $max = $attr['MAX']; }
	else { $max = 1; }
	if (isset($attr['MIN']) && $attr['MIN'] && $tag=='METER') { $min = $attr['MIN']; }
	else { $min = 0; }
	if ($max < $min) { $max = $min; }

	if (isset($attr['VALUE']) && ($attr['VALUE'] || $attr['VALUE']==='0')) {
		$value = $attr['VALUE'];
		if ($value < $min) { $value = $min; }
		else if ($value > $max) { $value = $max; }
	}
	else { $value = ''; }

	if (isset($attr['LOW']) && $attr['LOW']) { $low = $attr['LOW']; }
	else { $low = $min; }
	if ($low < $min) { $low = $min; }
	else if ($low > $max) { $low = $max; }
	if (isset($attr['HIGH']) && $attr['HIGH']) { $high = $attr['HIGH']; }
	else { $high = $max; }
	if ($high < $low) { $high = $low; }
	else if ($high > $max) { $high = $max; }
	if (isset($attr['OPTIMUM']) && $attr['OPTIMUM']) { $optimum = $attr['OPTIMUM']; }
	else { $optimum = $min + (($max-$min)/2); }
	if ($optimum < $min) { $optimum = $min; }
	else if ($optimum > $max) { $optimum = $max; }
	if (isset($attr['TYPE']) && $attr['TYPE']) { $type = $attr['TYPE']; }
	else { $type = ''; }
	$objattr = array();
		$objattr['margin_top'] = 0;
		$objattr['margin_bottom'] = 0;
		$objattr['margin_left'] = 0;
		$objattr['margin_right'] = 0;
		$objattr['padding_top'] = 0;
		$objattr['padding_bottom'] = 0;
		$objattr['padding_left'] = 0;
		$objattr['padding_right'] = 0;
		$objattr['width'] = 0;
		$objattr['height'] = 0;
		$objattr['border_top']['w'] = 0;
		$objattr['border_bottom']['w'] = 0;
		$objattr['border_left']['w'] = 0;
		$objattr['border_right']['w'] = 0;

		$properties = $this->MergeCSS('',$tag,$attr);
		if(isset($properties ['DISPLAY']) && strtolower($properties ['DISPLAY'])=='none') {
			return;
		}
		$objattr['visibility'] = 'visible';
		if (isset($properties['VISIBILITY'])) {
			$v = strtolower($properties['VISIBILITY']);
			if (($v == 'hidden' || $v == 'printonly' || $v == 'screenonly') && $this->visibility=='visible') {
				$objattr['visibility'] = $v;
			}
		}

		if (isset($properties['MARGIN-TOP'])) { $objattr['margin_top']=$this->ConvertSize($properties['MARGIN-TOP'],$this->blk[$this->blklvl]['inner_width'],$this->FontSize,false); }
		if (isset($properties['MARGIN-BOTTOM'])) { $objattr['margin_bottom'] = $this->ConvertSize($properties['MARGIN-BOTTOM'],$this->blk[$this->blklvl]['inner_width'],$this->FontSize,false); }
		if (isset($properties['MARGIN-LEFT'])) { $objattr['margin_left'] = $this->ConvertSize($properties['MARGIN-LEFT'],$this->blk[$this->blklvl]['inner_width'],$this->FontSize,false); }
		if (isset($properties['MARGIN-RIGHT'])) { $objattr['margin_right'] = $this->ConvertSize($properties['MARGIN-RIGHT'],$this->blk[$this->blklvl]['inner_width'],$this->FontSize,false); }

		if (isset($properties['PADDING-TOP'])) { $objattr['padding_top']=$this->ConvertSize($properties['PADDING-TOP'],$this->blk[$this->blklvl]['inner_width'],$this->FontSize,false); }
		if (isset($properties['PADDING-BOTTOM'])) { $objattr['padding_bottom'] = $this->ConvertSize($properties['PADDING-BOTTOM'],$this->blk[$this->blklvl]['inner_width'],$this->FontSize,false); }
		if (isset($properties['PADDING-LEFT'])) { $objattr['padding_left'] = $this->ConvertSize($properties['PADDING-LEFT'],$this->blk[$this->blklvl]['inner_width'],$this->FontSize,false); }
		if (isset($properties['PADDING-RIGHT'])) { $objattr['padding_right'] = $this->ConvertSize($properties['PADDING-RIGHT'],$this->blk[$this->blklvl]['inner_width'],$this->FontSize,false); }

		if (isset($properties['BORDER-TOP'])) { $objattr['border_top'] = $this->border_details($properties['BORDER-TOP']); }
		if (isset($properties['BORDER-BOTTOM'])) { $objattr['border_bottom'] = $this->border_details($properties['BORDER-BOTTOM']); }
		if (isset($properties['BORDER-LEFT'])) { $objattr['border_left'] = $this->border_details($properties['BORDER-LEFT']); }
		if (isset($properties['BORDER-RIGHT'])) { $objattr['border_right'] = $this->border_details($properties['BORDER-RIGHT']); }

		if (isset($properties['VERTICAL-ALIGN'])) { $objattr['vertical-align'] = $align[strtolower($properties['VERTICAL-ALIGN'])]; }
		$w = 0;
		$h = 0;
		if(isset($properties['WIDTH'])) $w = $this->ConvertSize($properties['WIDTH'],$this->blk[$this->blklvl]['inner_width'],$this->FontSize,false);
		if(isset($properties['HEIGHT'])) $h = $this->ConvertSize($properties['HEIGHT'],$this->blk[$this->blklvl]['inner_width'],$this->FontSize,false);

		if(isset($attr['WIDTH'])) $w = $this->ConvertSize($attr['WIDTH'],$this->blk[$this->blklvl]['inner_width'],$this->FontSize,false);
		if(isset($attr['HEIGHT'])) $h = $this->ConvertSize($attr['HEIGHT'],$this->blk[$this->blklvl]['inner_width'],$this->FontSize,false);
		if (isset($properties['OPACITY']) && $properties['OPACITY'] > 0 && $properties['OPACITY'] <= 1) { $objattr['opacity'] = $properties['OPACITY']; }
		if ($this->HREF) {
			if (strpos($this->HREF,".") === false && strpos($this->HREF,"@") !== 0) {
				$href = $this->HREF;
				while(array_key_exists($href,$this->internallink)) $href="#".$href;
	    			$this->internallink[$href] = $this->AddLink();
				$objattr['link'] = $this->internallink[$href];
			}
			else { $objattr['link'] = $this->HREF; }
		}
		$extraheight = $objattr['padding_top'] + $objattr['padding_bottom'] + $objattr['margin_top'] + $objattr['margin_bottom'] + $objattr['border_top']['w'] + $objattr['border_bottom']['w'];
		$extrawidth = $objattr['padding_left'] + $objattr['padding_right'] + $objattr['margin_left'] + $objattr['margin_right'] + $objattr['border_left']['w'] + $objattr['border_right']['w'];

		// Image file
		if (!class_exists('meter', false)) {
			include(_MPDF_PATH.'classes/meter.php');
		}
		$this->meter = new meter();
		$svg = $this->meter->makeSVG(strtolower($tag), $type, $value, $max, $min, $optimum, $low, $high);
		//Save to local file
		$srcpath= _MPDF_TEMP_PATH.'_tempSVG'.RAND(1,10000).'_'.strtolower($tag).'.svg';
		file_put_contents($srcpath, $svg);
		$orig_srcpath = $srcpath;
		$this->GetFullPath($srcpath);

		$info=$this->_getImage($srcpath, true, true, $orig_srcpath);
		if(!$info) {
			$info = $this->_getImage($this->noImageFile);
			if ($info) {
				$srcpath = $this->noImageFile;
				$w = ($info['w'] * (25.4/$this->dpi));
				$h = ($info['h'] * (25.4/$this->dpi));
			}
		}
		if(!$info) break;

		$objattr['file'] = $srcpath;
		//Default width and height calculation if needed
		if($w==0 and $h==0) {
			// SVG units are pixels
			$w = $this->FontSize/(10/_MPDFK) * abs($info['w'])/_MPDFK;	// mPDF 5.5.21
			$h = $this->FontSize/(10/_MPDFK) * abs($info['h'])/_MPDFK;
		}
		// IF WIDTH OR HEIGHT SPECIFIED
		if($w==0)  $w=abs($h*$info['w']/$info['h']);
		if($h==0)	$h=abs($w*$info['h']/$info['w']);

		// Resize to maximum dimensions of page
		$maxWidth = $this->blk[$this->blklvl]['inner_width'];
   		$maxHeight = $this->h - ($this->tMargin + $this->bMargin + 1) ;
		if ($this->fullImageHeight) { $maxHeight = $this->fullImageHeight; }
		if ($w + $extrawidth > $maxWidth ) {
			$w = $maxWidth - $extrawidth;
			$h=abs($w*$info['h']/$info['w']);
		}

		if ($h + $extraheight > $maxHeight ) {
			$h = $maxHeight - $extraheight;
			$w=abs($h*$info['w']/$info['h']);
		}
		$objattr['type'] = 'image';
		$objattr['itype'] = $info['type'];

		$objattr['orig_h'] = $info['h'];
		$objattr['orig_w'] = $info['w'];
		$objattr['wmf_x'] = $info['x'];
		$objattr['wmf_y'] = $info['y'];
		$objattr['height'] = $h + $extraheight;
		$objattr['width'] = $w + $extrawidth;
		$objattr['image_height'] = $h;
		$objattr['image_width'] = $w;
		$e = "\xbb\xa4\xactype=image,objattr=".serialize($objattr)."\xbb\xa4\xac";
		$properties = array();
		if ($this->tableLevel) {
			$this->_saveCellTextBuffer($e, $this->HREF);
			$this->cell[$this->row][$this->col]['s'] += $objattr['width'] ;
		}
		else {
			$this->_saveTextBuffer($e, $this->HREF);
		}

	break;


    case 'BR':
	// Added mPDF 3.0 Float DIV - CLEAR
	if (isset($attr['STYLE'])) {
		$properties = $this->readInlineCSS($attr['STYLE']);
	}


	if($this->tableLevel) {
	   
	   if ($this->blockjustfinished || $this->listjustfinished) {
		$this->_saveCellTextBuffer("\n");
	   }

		$this->_saveCellTextBuffer("\n");
		if (!isset($this->cell[$this->row][$this->col]['maxs'])) {
			$this->cell[$this->row][$this->col]['maxs'] = $this->cell[$this->row][$this->col]['s'];
		}
		elseif($this->cell[$this->row][$this->col]['maxs'] < $this->cell[$this->row][$this->col]['s']) {
			$this->cell[$this->row][$this->col]['maxs'] = $this->cell[$this->row][$this->col]['s'];
		}
		$this->cell[$this->row][$this->col]['s'] = 0 ;// reset
	}
	else  {
		if (count($this->textbuffer)) {
			$this->textbuffer[count($this->textbuffer)-1][0] = preg_replace('/ $/','',$this->textbuffer[count($this->textbuffer)-1][0]);
		}
		$this->_saveTextBuffer("\n");
	}	// *TABLES*
	$this->ignorefollowingspaces = true;
	$this->blockjustfinished=false;
	$this->listjustfinished=false;

	$this->linebreakjustfinished=true;
	break;


	// *********** BLOCKS  ********************


    case 'PRE':
	$this->ispre=true;	// ADDED - Prevents left trim of textbuffer in printbuffer()

    case 'DIV':
    case 'FORM':
    case 'CENTER':

    case 'BLOCKQUOTE':
    case 'ADDRESS':

    case 'CAPTION':
    case 'P':
    case 'H1':
    case 'H2':
    case 'H3':
    case 'H4':
    case 'H5':
    case 'H6':
    case 'DL':
    case 'DT':
    case 'DD':
    case 'FIELDSET':
    // mPDF 5.5.22
    case 'DETAILS':
    case 'SUMMARY':
    // mPDF 5.5.09
    case 'ARTICLE':
    case 'ASIDE':
    case 'FIGURE':
    case 'FIGCAPTION':
    case 'FOOTER':
    case 'HEADER':
    case 'HGROUP':
    case 'NAV':
    case 'SECTION':
	$p = $this->PreviewBlockCSS($tag,$attr);
	if(isset($p['DISPLAY']) && strtolower($p['DISPLAY'])=='none') {
		$this->blklvl++;
		$this->blk[$this->blklvl]['hide'] = true;
		return;
	}
	if($tag == 'CAPTION') {
		// position is written in AdjstHTML
		if (isset($attr['POSITION']) && strtolower($attr['POSITION'])=='bottom') { $divpos = 'B'; }
		else { $divpos = 'T'; }
		if (isset($attr['ALIGN']) && strtolower($attr['ALIGN'])=='bottom') { $cappos = 'B'; }
		else if (isset($p['CAPTION-SIDE']) && strtolower($p['CAPTION-SIDE'])=='bottom') { $cappos = 'B'; }
		else { $cappos = 'T'; }
		if (isset($attr['ALIGN'])) { unset($attr['ALIGN']); }
		if ($cappos != $divpos) {
			$this->blklvl++;
			$this->blk[$this->blklvl]['hide'] = true;
			return;
		}
	}



	// Start Block
	$this->ignorefollowingspaces = true;

	if ($this->blockjustfinished && !count($this->textbuffer) && $this->y != $this->tMargin && $this->collapseBlockMargins) { $lastbottommargin = $this->lastblockbottommargin; }
	else { $lastbottommargin = 0; }
	$this->lastblockbottommargin = 0;
	$this->blockjustfinished=false;

	if ($this->listlvl>0) { return; }

	$this->InlineProperties = array();
	$this->spanlvl = 0;
	$this->listjustfinished=false;
	$this->divbegin=true;

	$this->linebreakjustfinished=false;

	if ($this->tableLevel) {
	   
	   // If already something on the line
	   if ($this->cell[$this->row][$this->col]['s'] > 0  && !$this->nestedtablejustfinished ) {
		$this->_saveCellTextBuffer("\n");
		if (!isset($this->cell[$this->row][$this->col]['maxs'])) {
			$this->cell[$this->row][$this->col]['maxs'] = $this->cell[$this->row][$this->col]['s'];
		}
		elseif($this->cell[$this->row][$this->col]['maxs'] < $this->cell[$this->row][$this->col]['s']) {
			$this->cell[$this->row][$this->col]['maxs'] = $this->cell[$this->row][$this->col]['s'];
		}
		$this->cell[$this->row][$this->col]['s'] = 0 ;// reset
	   }
	   // Cannot set block properties inside table - use Bold to indicate h1-h6
	   if ($tag == 'CENTER' && $this->tdbegin) { $this->cell[$this->row][$this->col]['a'] = $align['center']; }

		$this->InlineProperties['BLOCKINTABLE'] = $this->saveInlineProperties();
		$properties = $this->MergeCSS('',$tag,$attr);
		if (!empty($properties)) $this->setCSS($properties,'INLINE');


	   break;
	}

	if ($tag == 'P' || $tag == 'DT' || $tag == 'DD') { $this->lastoptionaltag = $tag; } // Save current HTML specified optional endtag
	else { $this->lastoptionaltag = ''; }

	if ($this->lastblocklevelchange == 1) { $blockstate = 1; }	// Top margins/padding only
	else if ($this->lastblocklevelchange < 1) { $blockstate = 0; }	// NO margins/padding
	$this->printbuffer($this->textbuffer,$blockstate);
	$this->textbuffer=array();

	$save_blklvl = $this->blklvl;
	$save_blk = $this->blk;
	$save_silp = $this->saveInlineProperties();
	$save_spanlvl = $this->spanlvl;
	$save_ilp = $this->InlineProperties;

	$this->blklvl++;

	$currblk =& $this->blk[$this->blklvl];
	$this->initialiseBlock($currblk);
	$prevblk =& $this->blk[$this->blklvl-1];

	$currblk['tag'] = $tag;
	$currblk['attr'] = $attr;

	$this->Reset();
	$properties = $this->MergeCSS('BLOCK',$tag,$attr);
	$pagesel = '';

	// If page-box has changed AND/OR PAGE-BREAK-BEFORE
	$save_cols = false;
	if (($pagesel && $pagesel != $this->page_box['current']) || (isset($properties['PAGE-BREAK-BEFORE']) && $properties['PAGE-BREAK-BEFORE'])) {
		if ($this->blklvl>1) {
			// Close any open block tags
			for ($b= $this->blklvl;$b>0;$b--) { $this->CloseTag($this->blk[$b]['tag']); }
			// Output any text left in buffer
			if (count($this->textbuffer)) { $this->printbuffer($this->textbuffer); $this->textbuffer=array(); }
		}


		// Must Add new page if changed page properties
		if (isset($properties['PAGE-BREAK-BEFORE'])) {
			if (strtoupper($properties['PAGE-BREAK-BEFORE']) == 'RIGHT') { $this->AddPage($this->CurOrientation,'NEXT-ODD','','','','','', '','', '','','','','','',0,0,0,0,$pagesel); }
			else if (strtoupper($properties['PAGE-BREAK-BEFORE']) == 'LEFT') { $this->AddPage($this->CurOrientation,'NEXT-EVEN','','','','','', '','', '','','','','','',0,0,0,0,$pagesel); }
			else if (strtoupper($properties['PAGE-BREAK-BEFORE']) == 'ALWAYS') { $this->AddPage($this->CurOrientation,'','','','','','', '','', '','','','','','',0,0,0,0,$pagesel); }
		}

		// if using htmlheaders, the headers need to be rewritten when new page
		// done by calling WriteHTML() within resethtmlheaders
		// so block is reset to 0 - now we need to resurrect it
		// As in WriteHTML() initialising
		if (!($this->restoreBlockPagebreaks && isset($properties['PAGE-BREAK-BEFORE']) && $properties['PAGE-BREAK-BEFORE'])) {
			$this->blklvl = 0;
			$this->lastblocklevelchange = 0;
			$this->blk = array();
			$this->initialiseBlock($this->blk[0]);
			$this->blk[0]['width'] =& $this->pgwidth;
			$this->blk[0]['inner_width'] =& $this->pgwidth;
			$this->blk[0]['blockContext'] = $this->blockContext;
			$properties = $this->MergeCSS('BLOCK','BODY','');
			$this->setCSS($properties,'','BODY');
			$this->blklvl++;
			$currblk =& $this->blk[$this->blklvl];
			$prevblk =& $this->blk[$this->blklvl-1];

			$this->initialiseBlock($currblk);
			$currblk['tag'] = $tag;
			$currblk['attr'] = $attr;

			$this->Reset();
			$properties = $this->MergeCSS('BLOCK',$tag,$attr);
		}
		if ($this->restoreBlockPagebreaks && isset($properties['PAGE-BREAK-BEFORE']) && $properties['PAGE-BREAK-BEFORE']) {
			$this->blk = $save_blk;
			// Re-open block tags
			$t = $this->blk[0]['tag'];
			$a = $this->blk[0]['attr'];
			$this->blklvl = 0;
			for ($b=0; $b<=$save_blklvl;$b++) {
				$tc = $t;
				$ac = $a;
				$t = $this->blk[$b+1]['tag'];
				$a = $this->blk[$b+1]['attr'];
				unset($this->blk[$b+1]);
				$this->OpenTag($tc,$ac);
			}
			$this->spanlvl = $save_spanlvl;
			$this->InlineProperties = $save_ilp;
			$this->restoreInlineProperties($save_silp);
		}
	}

	if (isset($properties['PAGE-BREAK-INSIDE']) && strtoupper($properties['PAGE-BREAK-INSIDE']) == 'AVOID' && !$this->ColActive && !$this->keep_block_together) {
		$currblk['keep_block_together'] = 1;
		$this->kt_y00 = $this->y;
		$this->kt_p00 = $this->page;
		$this->keep_block_together = 1;
		$this->divbuffer = array();
		$this->ktLinks = array();
		$this->ktAnnots = array();
		$this->ktForms = array();
		$this->ktBlock = array();
		$this->ktReference = array();
		$this->ktBMoutlines = array();
		$this->_kttoc = array();
	}
	if ($lastbottommargin && isset($properties['MARGIN-TOP']) && $properties['MARGIN-TOP'] && empty($properties['FLOAT'])) { $currblk['lastbottommargin'] = $lastbottommargin; }

	// mPDF 5.6.01  - LAYERS
	if (isset($properties['Z-INDEX']) && $this->currentlayer==0) {
		$v = intval($properties['Z-INDEX']);
		if ($v > 0) {
			$currblk['z-index'] = $v;
			$this->BeginLayer($v);
		}
	}

	$this->setCSS($properties,'BLOCK',$tag); //name(id/class/style) found in the CSS array!
	$currblk['InlineProperties'] = $this->saveInlineProperties();

	if (isset($properties['VISIBILITY'])) {
		$v = strtolower($properties['VISIBILITY']);
		if (($v == 'hidden' || $v == 'printonly' || $v == 'screenonly') && $this->visibility=='visible' && !$this->tableLevel) {
			$currblk['visibility'] = $v;
			$this->SetVisibility($v);
		}
	}

	if(isset($attr['DIR']) && $attr['DIR']) { $currblk['direction'] = strtolower($attr['DIR']); }
	if(isset($attr['ALIGN']) && $attr['ALIGN']) { $currblk['block-align'] = $align[strtolower($attr['ALIGN'])]; }

	if (isset($properties['HEIGHT'])) {
		$currblk['css_set_height'] = $this->ConvertSize($properties['HEIGHT'],($this->h - $this->tMargin - $this->bMargin),$this->FontSize,false);
		if (($currblk['css_set_height'] + $this->y) > $this->PageBreakTrigger && $this->y > $this->tMargin+5 && $currblk['css_set_height'] < ($this->h - ($this->tMargin + $this->bMargin))) { $this->AddPage($this->CurOrientation); }
	}
	else { $currblk['css_set_height'] = false; }


	// Added mPDF 3.0 Float DIV


	$container_w = $prevblk['inner_width'];
	$bdr = $currblk['border_right']['w'];
	$bdl = $currblk['border_left']['w'];
	$pdr = $currblk['padding_right'];
	$pdl = $currblk['padding_left'];

	if (isset($currblk['css_set_width'])) { $setwidth = $currblk['css_set_width']; }
	else { $setwidth = 0; }





	// Hanging indent - if negative indent: ensure padding is >= indent
	if(!isset($currblk['text_indent'])) { $currblk['text_indent'] = null; }
	if(!isset($currblk['inner_width'])) { $currblk['inner_width'] = null; }
	$cbti = $this->ConvertSize($currblk['text_indent'],$this->blk[$this->blklvl]['inner_width'],$this->FontSize,false);
	if ($cbti < 0) {
	  $hangind = -($cbti);
		$currblk['padding_left'] = max($currblk['padding_left'],$hangind);
	}

	if (isset($currblk['css_set_width'])) {
	  if (isset($properties['MARGIN-LEFT']) && isset($properties['MARGIN-RIGHT']) && strtolower($properties['MARGIN-LEFT'])=='auto' && strtolower($properties['MARGIN-RIGHT'])=='auto') {
		  // Try to reduce margins to accomodate - if still too wide, set margin-right/left=0 (reduces width)
		  $anyextra = $prevblk['inner_width'] - ($currblk['css_set_width'] + $currblk['border_left']['w'] + $currblk['padding_left'] + $currblk['border_right']['w'] + $currblk['padding_right']);
		  if ($anyextra>0) {
			$currblk['margin_left'] = $currblk['margin_right'] = $anyextra /2;
		  }
		  else {
			$currblk['margin_left'] = $currblk['margin_right'] = 0;
		  }
	  }
	  else if (isset($properties['MARGIN-LEFT']) && strtolower($properties['MARGIN-LEFT'])=='auto') {
		  // Try to reduce margin-left to accomodate - if still too wide, set margin-left=0 (reduces width)
		  $currblk['margin_left'] = $prevblk['inner_width'] - ($currblk['css_set_width'] + $currblk['border_left']['w'] + $currblk['padding_left'] + $currblk['border_right']['w'] + $currblk['padding_right'] + $currblk['margin_right']);
		  if ($currblk['margin_left'] < 0) {
			$currblk['margin_left'] = 0;
		  }
	  }
	  else if (isset($properties['MARGIN-RIGHT']) && strtolower($properties['MARGIN-RIGHT'])=='auto') {
		  // Try to reduce margin-right to accomodate - if still too wide, set margin-right=0 (reduces width)
		  $currblk['margin_right'] = $prevblk['inner_width'] - ($currblk['css_set_width'] + $currblk['border_left']['w'] + $currblk['padding_left'] + $currblk['border_right']['w'] + $currblk['padding_right'] + $currblk['margin_left']);
		  if ($currblk['margin_right'] < 0) {
			$currblk['margin_right'] = 0;
		  }
	  }
	  else {
		// Try to reduce margin-left to accomodate - if still too wide, set margin-left=0 (reduces width)
		  // Try to reduce margin-right to accomodate - if still too wide, set margin-right=0 (reduces width)
		  $currblk['margin_right'] = $prevblk['inner_width'] - ($currblk['css_set_width'] + $currblk['border_left']['w'] + $currblk['padding_left'] + $currblk['border_right']['w'] + $currblk['padding_right'] + $currblk['margin_left']);
		  if ($currblk['margin_right'] < 0) {
			$currblk['margin_right'] = 0;
		  }
	  }
	}

	$currblk['outer_left_margin'] = $prevblk['outer_left_margin'] + $currblk['margin_left'] + $prevblk['border_left']['w'] + $prevblk['padding_left'];
	$currblk['outer_right_margin'] = $prevblk['outer_right_margin']  + $currblk['margin_right'] + $prevblk['border_right']['w'] + $prevblk['padding_right'];

	$currblk['width'] = $this->pgwidth - ($currblk['outer_right_margin'] + $currblk['outer_left_margin']);
	$currblk['inner_width'] = $currblk['width'] - ($currblk['border_left']['w'] + $currblk['padding_left'] + $currblk['border_right']['w'] + $currblk['padding_right']);

	// Check DIV is not now too narrow to fit text
	$mw = 2*$this->GetCharWidth('W',false);
	if ($currblk['inner_width'] < $mw) {
		$currblk['padding_left'] = 0;
		$currblk['padding_right'] = 0;
		$currblk['border_left']['w'] = 0.2;
		$currblk['border_right']['w'] = 0.2;
		$currblk['margin_left'] = 0;
		$currblk['margin_right'] = 0;
		$currblk['outer_left_margin'] = $prevblk['outer_left_margin'] + $currblk['margin_left'] + $prevblk['border_left']['w'] + $prevblk['padding_left'];
		$currblk['outer_right_margin'] = $prevblk['outer_right_margin']  + $currblk['margin_right'] + $prevblk['border_right']['w'] + $prevblk['padding_right'];
		$currblk['width'] = $this->pgwidth - ($currblk['outer_right_margin'] + $currblk['outer_left_margin']);
		$currblk['inner_width'] = $this->pgwidth - ($currblk['outer_right_margin'] + $currblk['outer_left_margin'] + $currblk['border_left']['w'] + $currblk['padding_left'] + $currblk['border_right']['w'] + $currblk['padding_right']);
//		if ($currblk['inner_width'] < $mw) { $this->Error("DIV is too narrow for text to fit!"); }
	}

	$this->x = $this->lMargin + $currblk['outer_left_margin'];


	if ($this->use_kwt && isset($attr['KEEP-WITH-TABLE']) && !$this->ColActive && !$this->keep_block_together) {
		$this->kwt = true;
		$this->kwt_y0 = $this->y;
		$this->kwt_x0 = $this->x;
		$this->kwt_height = 0;
		$this->kwt_buffer = array();
		$this->kwt_Links = array();
		$this->kwt_Annots = array();
		$this->kwt_moved = false;
		$this->kwt_saved = false;
		$this->kwt_Reference = array();
		$this->kwt_BMoutlines = array();
		$this->kwt_toc = array();
	}
	else {
		$this->kwt = false;
	}	// *TABLES*

	//Save x,y coords in case we need to print borders...
	$currblk['y0'] = $this->y;
	$currblk['x0'] = $this->x;
	$currblk['startpage'] = $this->page;
	$this->oldy = $this->y;

	$this->lastblocklevelchange = 1 ;

	break;

    case 'HR':
	// Added mPDF 3.0 Float DIV - CLEAR
	if (isset($attr['STYLE'])) {
		$properties = $this->readInlineCSS($attr['STYLE']);
	}

	$this->ignorefollowingspaces = true;

	$objattr = array();
		$objattr['margin_top'] = 0;
		$objattr['margin_bottom'] = 0;
		$objattr['margin_left'] = 0;
		$objattr['margin_right'] = 0;
		$objattr['width'] = 0;
		$objattr['height'] = 0;
		$objattr['border_top']['w'] = 0;
		$objattr['border_bottom']['w'] = 0;
		$objattr['border_left']['w'] = 0;
		$objattr['border_right']['w'] = 0;
	$properties = $this->MergeCSS('',$tag,$attr);
	if (isset($properties['MARGIN-TOP'])) { $objattr['margin_top'] = $this->ConvertSize($properties['MARGIN-TOP'],$this->blk[$this->blklvl]['inner_width'],$this->FontSize,false); }
	if (isset($properties['MARGIN-BOTTOM'])) { $objattr['margin_bottom'] = $this->ConvertSize($properties['MARGIN-BOTTOM'],$this->blk[$this->blklvl]['inner_width'],$this->FontSize,false); }
	if (isset($properties['WIDTH'])) { $objattr['width'] = $this->ConvertSize($properties['WIDTH'],$this->blk[$this->blklvl]['inner_width']); }
	if (isset($properties['TEXT-ALIGN'])) { $objattr['align'] = $align[strtolower($properties['TEXT-ALIGN'])]; }

	if (isset($properties['MARGIN-LEFT']) && strtolower($properties['MARGIN-LEFT'])=='auto') {
		$objattr['align'] = 'R';
	}
	if (isset($properties['MARGIN-RIGHT']) && strtolower($properties['MARGIN-RIGHT'])=='auto') {
		$objattr['align'] = 'L';
		if (isset($properties['MARGIN-RIGHT']) && strtolower($properties['MARGIN-RIGHT'])=='auto' && isset($properties['MARGIN-LEFT']) && strtolower($properties['MARGIN-LEFT'])=='auto') {
			$objattr['align'] = 'C';
		}
	}
	if (isset($properties['COLOR'])) { $objattr['color'] = $this->ConvertColor($properties['COLOR']); }
	if (isset($properties['HEIGHT'])) { $objattr['linewidth'] = $this->ConvertSize($properties['HEIGHT'],$this->blk[$this->blklvl]['inner_width'],$this->FontSize,false); }

	if(isset($attr['WIDTH']) && $attr['WIDTH'] != '') $objattr['width'] = $this->ConvertSize($attr['WIDTH'],$this->blk[$this->blklvl]['inner_width']);
	if(isset($attr['ALIGN']) && $attr['ALIGN'] != '') $objattr['align'] = $align[strtolower($attr['ALIGN'])];
	if(isset($attr['COLOR']) && $attr['COLOR'] != '') $objattr['color'] = $this->ConvertColor($attr['COLOR']);

	if ($this->tableLevel) {
		$objattr['W-PERCENT'] = 100;
		if (isset($properties['WIDTH']) && stristr($properties['WIDTH'],'%')) {
			$properties['WIDTH'] += 0;  //make "90%" become simply "90"
			$objattr['W-PERCENT'] = $properties['WIDTH'];
		}
		if (isset($attr['WIDTH']) && stristr($attr['WIDTH'],'%')) {
			$attr['WIDTH'] += 0;  //make "90%" become simply "90"
			$objattr['W-PERCENT'] = $attr['WIDTH'];
		}
	}

	$objattr['type'] = 'hr';
	$objattr['height'] = $objattr['linewidth'] + $objattr['margin_top'] + $objattr['margin_bottom'];
	$e = "\xbb\xa4\xactype=image,objattr=".serialize($objattr)."\xbb\xa4\xac";

	// Clear properties - tidy up
	$properties = array();

	// Output it to buffers
	if ($this->tableLevel) {
		if (!isset($this->cell[$this->row][$this->col]['maxs'])) {
			$this->cell[$this->row][$this->col]['maxs'] = $this->cell[$this->row][$this->col]['s'];
		}
		elseif($this->cell[$this->row][$this->col]['maxs'] < $this->cell[$this->row][$this->col]['s']) {
			$this->cell[$this->row][$this->col]['maxs'] = $this->cell[$this->row][$this->col]['s'];
		}
		$this->cell[$this->row][$this->col]['s'] = 0 ;// reset
		$this->_saveCellTextBuffer($e, $this->HREF);
	}
	else {
		$this->_saveTextBuffer($e, $this->HREF);
	}	// *TABLES*

	break;




	// *********** FORM ELEMENTS ********************



	// *********** GRAPH  ********************
     case 'JPGRAPH':
	if (!$this->useGraphs) { break; }
	if ($attr['TABLE']) { $gid = strtoupper($attr['TABLE']); }
	else { $gid = '0'; }
	if (!is_array($this->graphs[$gid]) || count($this->graphs[$gid])==0 ) { break; }
	include_once(_MPDF_PATH.'graph.php');
	$this->graphs[$gid]['attr'] = $attr;


	if (isset($this->graphs[$gid]['attr']['WIDTH']) && $this->graphs[$gid]['attr']['WIDTH']) {
		$this->graphs[$gid]['attr']['cWIDTH']=$this->ConvertSize($this->graphs[$gid]['attr']['WIDTH'],$pgwidth);
	}	// mm
	if (isset($this->graphs[$gid]['attr']['HEIGHT']) && $this->graphs[$gid]['attr']['HEIGHT']) {
		$this->graphs[$gid]['attr']['cHEIGHT']=$this->ConvertSize($this->graphs[$gid]['attr']['HEIGHT'],$pgwidth);
	}

	$graph_img = print_graph($this->graphs[$gid],$this->blk[$this->blklvl]['inner_width']);
	if ($graph_img) {
		if(isset($attr['ROTATE'])) {
		   if ($attr['ROTATE']==90 || $attr['ROTATE']==-90) {
			$tmpw = $graph_img['w'];
			$graph_img['w']= $graph_img['h'];
			$graph_img['h']= $tmpw;
		   }
		}
		$attr['SRC'] = $graph_img['file'];
		$attr['WIDTH'] = $graph_img['w'];
		$attr['HEIGHT'] = $graph_img['h'];
	}
	else { break; }

	// *********** IMAGE  ********************
    case 'IMG':
	$objattr = array();
		$objattr['margin_top'] = 0;
		$objattr['margin_bottom'] = 0;
		$objattr['margin_left'] = 0;
		$objattr['margin_right'] = 0;
		$objattr['padding_top'] = 0;
		$objattr['padding_bottom'] = 0;
		$objattr['padding_left'] = 0;
		$objattr['padding_right'] = 0;
		$objattr['width'] = 0;
		$objattr['height'] = 0;
		$objattr['border_top']['w'] = 0;
		$objattr['border_bottom']['w'] = 0;
		$objattr['border_left']['w'] = 0;
		$objattr['border_right']['w'] = 0;
	if(isset($attr['SRC']))	{
     		$srcpath = $attr['SRC'];
		$orig_srcpath = $attr['ORIG_SRC'];
		$properties = $this->MergeCSS('',$tag,$attr);
		if(isset($properties ['DISPLAY']) && strtolower($properties ['DISPLAY'])=='none') {
			return;
		}
		// mPDF 5.6.01  - LAYERS
		if (isset($properties['Z-INDEX']) && $this->currentlayer==0) {
			$v = intval($properties['Z-INDEX']);
			if ($v > 0) {
				$objattr['z-index'] = $v;
			}
		}

		$objattr['visibility'] = 'visible';
		if (isset($properties['VISIBILITY'])) {
			$v = strtolower($properties['VISIBILITY']);
			if (($v == 'hidden' || $v == 'printonly' || $v == 'screenonly') && $this->visibility=='visible') {
				$objattr['visibility'] = $v;
			}
		}

		// VSPACE and HSPACE converted to margins in MergeCSS
		if (isset($properties['MARGIN-TOP'])) { $objattr['margin_top']=$this->ConvertSize($properties['MARGIN-TOP'],$this->blk[$this->blklvl]['inner_width'],$this->FontSize,false); }
		if (isset($properties['MARGIN-BOTTOM'])) { $objattr['margin_bottom'] = $this->ConvertSize($properties['MARGIN-BOTTOM'],$this->blk[$this->blklvl]['inner_width'],$this->FontSize,false); }
		if (isset($properties['MARGIN-LEFT'])) { $objattr['margin_left'] = $this->ConvertSize($properties['MARGIN-LEFT'],$this->blk[$this->blklvl]['inner_width'],$this->FontSize,false); }
		if (isset($properties['MARGIN-RIGHT'])) { $objattr['margin_right'] = $this->ConvertSize($properties['MARGIN-RIGHT'],$this->blk[$this->blklvl]['inner_width'],$this->FontSize,false); }

		if (isset($properties['PADDING-TOP'])) { $objattr['padding_top']=$this->ConvertSize($properties['PADDING-TOP'],$this->blk[$this->blklvl]['inner_width'],$this->FontSize,false); }
		if (isset($properties['PADDING-BOTTOM'])) { $objattr['padding_bottom'] = $this->ConvertSize($properties['PADDING-BOTTOM'],$this->blk[$this->blklvl]['inner_width'],$this->FontSize,false); }
		if (isset($properties['PADDING-LEFT'])) { $objattr['padding_left'] = $this->ConvertSize($properties['PADDING-LEFT'],$this->blk[$this->blklvl]['inner_width'],$this->FontSize,false); }
		if (isset($properties['PADDING-RIGHT'])) { $objattr['padding_right'] = $this->ConvertSize($properties['PADDING-RIGHT'],$this->blk[$this->blklvl]['inner_width'],$this->FontSize,false); }

		if (isset($properties['BORDER-TOP'])) { $objattr['border_top'] = $this->border_details($properties['BORDER-TOP']); }
		if (isset($properties['BORDER-BOTTOM'])) { $objattr['border_bottom'] = $this->border_details($properties['BORDER-BOTTOM']); }
		if (isset($properties['BORDER-LEFT'])) { $objattr['border_left'] = $this->border_details($properties['BORDER-LEFT']); }
		if (isset($properties['BORDER-RIGHT'])) { $objattr['border_right'] = $this->border_details($properties['BORDER-RIGHT']); }

		if (isset($properties['VERTICAL-ALIGN'])) { $objattr['vertical-align'] = $align[strtolower($properties['VERTICAL-ALIGN'])]; }
		$w = 0;
		$h = 0;
		if(isset($properties['WIDTH'])) $w = $this->ConvertSize($properties['WIDTH'],$this->blk[$this->blklvl]['inner_width'],$this->FontSize,false);
		if(isset($properties['HEIGHT'])) $h = $this->ConvertSize($properties['HEIGHT'],$this->blk[$this->blklvl]['inner_width'],$this->FontSize,false);
		// mPDF 5.5.15
		$maxw=$maxh=$minw=$minh=false;
		if(isset($properties['MAX-WIDTH'])) $maxw = $this->ConvertSize($properties['MAX-WIDTH'],$this->blk[$this->blklvl]['inner_width'],$this->FontSize,false);
		if(isset($properties['MAX-HEIGHT'])) $maxh = $this->ConvertSize($properties['MAX-HEIGHT'],$this->blk[$this->blklvl]['inner_width'],$this->FontSize,false);
		if(isset($properties['MIN-WIDTH'])) $minw = $this->ConvertSize($properties['MIN-WIDTH'],$this->blk[$this->blklvl]['inner_width'],$this->FontSize,false);
		if(isset($properties['MIN-HEIGHT'])) $minh = $this->ConvertSize($properties['MIN-HEIGHT'],$this->blk[$this->blklvl]['inner_width'],$this->FontSize,false);

		if(isset($attr['WIDTH'])) $w = $this->ConvertSize($attr['WIDTH'],$this->blk[$this->blklvl]['inner_width'],$this->FontSize,false);
		if(isset($attr['HEIGHT'])) $h = $this->ConvertSize($attr['HEIGHT'],$this->blk[$this->blklvl]['inner_width'],$this->FontSize,false);
		if (isset($properties['OPACITY']) && $properties['OPACITY'] > 0 && $properties['OPACITY'] <= 1) { $objattr['opacity'] = $properties['OPACITY']; }
		if ($this->HREF) {
			if (strpos($this->HREF,".") === false && strpos($this->HREF,"@") !== 0) {
				$href = $this->HREF;
				while(array_key_exists($href,$this->internallink)) $href="#".$href;
	    			$this->internallink[$href] = $this->AddLink();
				$objattr['link'] = $this->internallink[$href];
			}
			else { $objattr['link'] = $this->HREF; }
		}
		$extraheight = $objattr['padding_top'] + $objattr['padding_bottom'] + $objattr['margin_top'] + $objattr['margin_bottom'] + $objattr['border_top']['w'] + $objattr['border_bottom']['w'];
		$extrawidth = $objattr['padding_left'] + $objattr['padding_right'] + $objattr['margin_left'] + $objattr['margin_right'] + $objattr['border_left']['w'] + $objattr['border_right']['w'];


		// Image file
		$info=$this->_getImage($srcpath, true, true, $orig_srcpath);
		if(!$info) {
			$info = $this->_getImage($this->noImageFile);
			if ($info) {
				$srcpath = $this->noImageFile;
				$w = ($info['w'] * (25.4/$this->dpi));
				$h = ($info['h'] * (25.4/$this->dpi));
			}
		}
		if(!$info) break;

		if(isset($attr['ROTATE'])) { $image_orientation = $attr['ROTATE']; }
		else if(isset($properties['IMAGE-ORIENTATION'])) { $image_orientation = $properties['IMAGE-ORIENTATION']; }
		else { $image_orientation = 0; }
		if($image_orientation) {
		   if ($image_orientation==90 || $image_orientation==-90 || $image_orientation==270) {
			$tmpw = $info['w'];
			$info['w'] = $info['h'];
			$info['h'] = $tmpw;
		   }
		   $objattr['ROTATE'] = $image_orientation;
		}

		$objattr['file'] = $srcpath;
		//Default width and height calculation if needed
		if($w==0 and $h==0) {
      	      if ($info['type']=='svg') {
				// SVG units are pixels
				$w = abs($info['w'])/_MPDFK;
				$h = abs($info['h'])/_MPDFK;
			}
			else {
				//Put image at default image dpi
				$w=($info['w']/_MPDFK) * (72/$this->img_dpi);
				$h=($info['h']/_MPDFK) * (72/$this->img_dpi);
			}
			if (isset($properties['IMAGE-RESOLUTION'])) {
				if (preg_match('/from-image/i', $properties['IMAGE-RESOLUTION']) && isset($info['set-dpi']) && $info['set-dpi']>0) {
					$w *= $this->img_dpi / $info['set-dpi'];
					$h *= $this->img_dpi / $info['set-dpi'];
				}
				else if (preg_match('/(\d+)dpi/i', $properties['IMAGE-RESOLUTION'], $m)) {
					$dpi = $m[1];
					if ($dpi > 0) {
						$w *= $this->img_dpi / $dpi;
						$h *= $this->img_dpi / $dpi;
					}
				}
			}
		}
		// IF WIDTH OR HEIGHT SPECIFIED
		if($w==0) $w=abs($h*$info['w']/$info['h']);
		if($h==0) $h=abs($w*$info['h']/$info['w']);

		// mPDF 5.5.15
		if ($minw && $w<$minw) { $w = $minw; $h=abs($w*$info['h']/$info['w']); }
		if ($maxw && $w>$maxw) { $w = $maxw; $h=abs($w*$info['h']/$info['w']); }
		if ($minh && $h<$minh) { $h = $minh; $w=abs($h*$info['w']/$info['h']); }
		if ($maxh && $h>$maxh) { $h = $maxh; $w=abs($h*$info['w']/$info['h']); }

		// Resize to maximum dimensions of page
		$maxWidth = $this->blk[$this->blklvl]['inner_width'];
   		$maxHeight = $this->h - ($this->tMargin + $this->bMargin + 1) ;
		if ($this->fullImageHeight) { $maxHeight = $this->fullImageHeight; }
		if ($w + $extrawidth > $maxWidth ) {
			$w = $maxWidth - $extrawidth;
			$h=abs($w*$info['h']/$info['w']);
		}

		if ($h + $extraheight > $maxHeight ) {
			$h = $maxHeight - $extraheight;
			$w=abs($h*$info['w']/$info['h']);
		}
		$objattr['type'] = 'image';
		$objattr['itype'] = $info['type'];

		$objattr['orig_h'] = $info['h'];
		$objattr['orig_w'] = $info['w'];
		if ($info['type']=='svg') {
			$objattr['wmf_x'] = $info['x'];
			$objattr['wmf_y'] = $info['y'];
		}
		$objattr['height'] = $h + $extraheight;
		$objattr['width'] = $w + $extrawidth;
		$objattr['image_height'] = $h;
		$objattr['image_width'] = $w;

		$e = "\xbb\xa4\xactype=image,objattr=".serialize($objattr)."\xbb\xa4\xac";

		// Clear properties - tidy up
		$properties = array();

		// Output it to buffers
		if ($this->tableLevel) {
			$this->_saveCellTextBuffer($e, $this->HREF);
			$this->cell[$this->row][$this->col]['s'] += $objattr['width'] ;
		}
		else {
			$this->_saveTextBuffer($e, $this->HREF);
		}	// *TABLES*
	}
	break;


	// *********** CIRCULAR TEXT = TEXTCIRCLE  ********************
    case 'TEXTCIRCLE':
		$objattr = array();
		$objattr['margin_top'] = 0;
		$objattr['margin_bottom'] = 0;
		$objattr['margin_left'] = 0;
		$objattr['margin_right'] = 0;
		$objattr['padding_top'] = 0;
		$objattr['padding_bottom'] = 0;
		$objattr['padding_left'] = 0;
		$objattr['padding_right'] = 0;
		$objattr['width'] = 0;
		$objattr['height'] = 0;
		$objattr['border_top']['w'] = 0;
		$objattr['border_bottom']['w'] = 0;
		$objattr['border_left']['w'] = 0;
		$objattr['border_right']['w'] = 0;
		$objattr['top-text'] = '';
		$objattr['bottom-text'] = '';
		$objattr['r'] = 20;	// radius (default value here for safety)
		$objattr['space-width'] = 120;
		$objattr['char-width'] = 100;

		$this->InlineProperties[$tag] = $this->saveInlineProperties();
		$properties = $this->MergeCSS('INLINE',$tag,$attr);

		if(isset($properties ['DISPLAY']) && strtolower($properties ['DISPLAY'])=='none') {
			return;
		}
		if (isset($attr['R'])) { $objattr['r']=$this->ConvertSize($attr['R'],$this->blk[$this->blklvl]['inner_width'],$this->FontSize,false); }
		if(isset($attr['TOP-TEXT'])) {
			$objattr['top-text'] = strcode2utf($attr['TOP-TEXT']);
			$objattr['top-text'] = $this->lesser_entity_decode($objattr['top-text']);
			if ($this->onlyCoreFonts)
				$objattr['top-text'] = mb_convert_encoding($objattr['top-text'], $this->mb_enc,'UTF-8');
		}
		if(isset($attr['BOTTOM-TEXT'])) {
			$objattr['bottom-text'] = strcode2utf($attr['BOTTOM-TEXT']);
			$objattr['bottom-text'] = $this->lesser_entity_decode($objattr['bottom-text']);
			if ($this->onlyCoreFonts)
				$objattr['bottom-text'] = mb_convert_encoding($objattr['bottom-text'], $this->mb_enc,'UTF-8');
		}
		if(isset($attr['SPACE-WIDTH']) && $attr['SPACE-WIDTH']) { $objattr['space-width'] = $attr['SPACE-WIDTH']; }
		if(isset($attr['CHAR-WIDTH']) && $attr['CHAR-WIDTH']) { $objattr['char-width'] = $attr['CHAR-WIDTH']; }

		// VISIBILITY
		$objattr['visibility'] = 'visible';
		if (isset($properties['VISIBILITY'])) {
			$v = strtolower($properties['VISIBILITY']);
			if (($v == 'hidden' || $v == 'printonly' || $v == 'screenonly') && $this->visibility=='visible') {
				$objattr['visibility'] = $v;
			}
		}
		// mPDF 5.5.23
		if (isset($properties['FONT-SIZE'])) {
		  if (strtolower($properties['FONT-SIZE'])=='auto') {
			if ($objattr['top-text'] && $objattr['bottom-text']) {
				$objattr['fontsize'] = -2;
			}
			else {
				$objattr['fontsize'] = -1;
			}
		  }
		  else {
			$mmsize = $this->ConvertSize($properties['FONT-SIZE'],($this->default_font_size/_MPDFK));
  			$this->SetFontSize($mmsize*_MPDFK,false);
			$objattr['fontsize'] = $this->FontSizePt;
		  }
		}
		// mPDF 5.5.23
		if(isset($attr['DIVIDER'])) {
			$objattr['divider'] = strcode2utf($attr['DIVIDER']);
			$objattr['divider'] = $this->lesser_entity_decode($objattr['divider']);
			if ($this->onlyCoreFonts)
				$objattr['divider'] = mb_convert_encoding($objattr['divider'], $this->mb_enc,'UTF-8');

		}

		if (isset($properties['COLOR'])) { $objattr['color'] = $this->ConvertColor($properties['COLOR']); }

		$objattr['fontstyle'] = '';
		if (isset($properties['FONT-WEIGHT'])) {
			if (strtoupper($properties['FONT-WEIGHT']) == 'BOLD')	{ $objattr['fontstyle'] .= 'B'; }
		}
		if (isset($properties['FONT-STYLE'])) {
			if (strtoupper($properties['FONT-STYLE']) == 'ITALIC') { $objattr['fontstyle'] .= 'I'; }
		}

		if (isset($properties['FONT-FAMILY'])) {
			$this->SetFont($properties['FONT-FAMILY'],$this->FontStyle,0,false);
		}
		$objattr['fontfamily'] = $this->FontFamily;

		// VSPACE and HSPACE converted to margins in MergeCSS
		if (isset($properties['MARGIN-TOP'])) { $objattr['margin_top']=$this->ConvertSize($properties['MARGIN-TOP'],$this->blk[$this->blklvl]['inner_width'],$this->FontSize,false); }
		if (isset($properties['MARGIN-BOTTOM'])) { $objattr['margin_bottom'] = $this->ConvertSize($properties['MARGIN-BOTTOM'],$this->blk[$this->blklvl]['inner_width'],$this->FontSize,false); }
		if (isset($properties['MARGIN-LEFT'])) { $objattr['margin_left'] = $this->ConvertSize($properties['MARGIN-LEFT'],$this->blk[$this->blklvl]['inner_width'],$this->FontSize,false); }
		if (isset($properties['MARGIN-RIGHT'])) { $objattr['margin_right'] = $this->ConvertSize($properties['MARGIN-RIGHT'],$this->blk[$this->blklvl]['inner_width'],$this->FontSize,false); }

		if (isset($properties['PADDING-TOP'])) { $objattr['padding_top']=$this->ConvertSize($properties['PADDING-TOP'],$this->blk[$this->blklvl]['inner_width'],$this->FontSize,false); }
		if (isset($properties['PADDING-BOTTOM'])) { $objattr['padding_bottom'] = $this->ConvertSize($properties['PADDING-BOTTOM'],$this->blk[$this->blklvl]['inner_width'],$this->FontSize,false); }
		if (isset($properties['PADDING-LEFT'])) { $objattr['padding_left'] = $this->ConvertSize($properties['PADDING-LEFT'],$this->blk[$this->blklvl]['inner_width'],$this->FontSize,false); }
		if (isset($properties['PADDING-RIGHT'])) { $objattr['padding_right'] = $this->ConvertSize($properties['PADDING-RIGHT'],$this->blk[$this->blklvl]['inner_width'],$this->FontSize,false); }

		if (isset($properties['BORDER-TOP'])) { $objattr['border_top'] = $this->border_details($properties['BORDER-TOP']); }
		if (isset($properties['BORDER-BOTTOM'])) { $objattr['border_bottom'] = $this->border_details($properties['BORDER-BOTTOM']); }
		if (isset($properties['BORDER-LEFT'])) { $objattr['border_left'] = $this->border_details($properties['BORDER-LEFT']); }
		if (isset($properties['BORDER-RIGHT'])) { $objattr['border_right'] = $this->border_details($properties['BORDER-RIGHT']); }

		if (isset($properties['OPACITY']) && $properties['OPACITY'] > 0 && $properties['OPACITY'] <= 1) { $objattr['opacity'] = $properties['OPACITY']; }
		if (isset($properties['BACKGROUND-COLOR']) && $properties['BACKGROUND-COLOR'] != '') { $objattr['bgcolor'] = $this->ConvertColor($properties['BACKGROUND-COLOR']); }
		else { $objattr['bgcolor'] = false; }
		if ($this->HREF) {
			if (strpos($this->HREF,".") === false && strpos($this->HREF,"@") !== 0) {
				$href = $this->HREF;
				while(array_key_exists($href,$this->internallink)) $href="#".$href;
	    			$this->internallink[$href] = $this->AddLink();
				$objattr['link'] = $this->internallink[$href];
			}
			else { $objattr['link'] = $this->HREF; }
		}
		$extraheight = $objattr['padding_top'] + $objattr['padding_bottom'] + $objattr['margin_top'] + $objattr['margin_bottom'] + $objattr['border_top']['w'] + $objattr['border_bottom']['w'];
		$extrawidth = $objattr['padding_left'] + $objattr['padding_right'] + $objattr['margin_left'] + $objattr['margin_right'] + $objattr['border_left']['w'] + $objattr['border_right']['w'];


		$w = $objattr['r']*2;
		$h = $w;
		$objattr['height'] = $h + $extraheight;
		$objattr['width'] = $w + $extrawidth;
		$objattr['type'] = 'textcircle';

		$e = "\xbb\xa4\xactype=image,objattr=".serialize($objattr)."\xbb\xa4\xac";

		// Clear properties - tidy up
		$properties = array();

		// Output it to buffers
		if ($this->tableLevel) {
			$this->_saveCellTextBuffer($e, $this->HREF);
			$this->cell[$this->row][$this->col]['s'] += $objattr['width'] ;
		}
		else {
			$this->_saveTextBuffer($e, $this->HREF);
		}	// *TABLES*

		if ($this->InlineProperties[$tag]) { $this->restoreInlineProperties($this->InlineProperties[$tag]); }
		unset($this->InlineProperties[$tag]);

		break;



    case 'TABLE': // TABLE-BEGIN
	$this->tdbegin = false;
	$this->lastoptionaltag = '';
	// Disable vertical justification in columns
	if ($this->lastblocklevelchange == 1) { $blockstate = 1; }	// Top margins/padding only
	else if ($this->lastblocklevelchange < 1) { $blockstate = 0; }	// NO margins/padding
	// called from block after new div e.g. <div> ... <table> ...    Outputs block top margin/border and padding
	if (count($this->textbuffer) == 0 && $this->lastblocklevelchange == 1 && !$this->tableLevel && !$this->kwt) {
		$this->newFlowingBlock( $this->blk[$this->blklvl]['width'],$this->lineheight,'',false,false,1,true, $this->blk[$this->blklvl]['direction']);
		$this->finishFlowingBlock(true);	// true = END of flowing block
	}
	else if (!$this->tableLevel && count($this->textbuffer)) { $this->printbuffer($this->textbuffer,$blockstate); }

	$this->textbuffer=array();
	$this->lastblocklevelchange = -1;
	if ($this->tableLevel) {	// i.e. now a nested table coming...
		// Save current level table
		// mPDF 5.4.10
		$this->cell['PARENTCELL'] = $this->saveInlineProperties();
		$this->table[$this->tableLevel][$this->tbctr[$this->tableLevel]]['baseProperties']= $this->base_table_properties;
		$this->table[$this->tableLevel][$this->tbctr[$this->tableLevel]]['cells'] = $this->cell;
		$this->table[$this->tableLevel][$this->tbctr[$this->tableLevel]]['currrow'] = $this->row;
		$this->table[$this->tableLevel][$this->tbctr[$this->tableLevel]]['currcol'] = $this->col;
	}
	$this->tableLevel++;
	$this->tbCSSlvl++;

	if ($this->tableLevel>1) {	// inherit table properties from cell in which nested
		$this->base_table_properties['FONT-KERNING'] = $this->kerning ;
		$this->base_table_properties['LETTER-SPACING'] = $this->lSpacingCSS ;
		$this->base_table_properties['WORD-SPACING'] = $this->wSpacingCSS ;
	}

	if (isset($this->tbctr[$this->tableLevel])) { $this->tbctr[$this->tableLevel]++; }
	else { $this->tbctr[$this->tableLevel] = 1; }

	$this->table[$this->tableLevel][$this->tbctr[$this->tableLevel]]['level'] = $this->tableLevel;
	$this->table[$this->tableLevel][$this->tbctr[$this->tableLevel]]['levelid'] = $this->tbctr[$this->tableLevel];

	if ($this->tableLevel > $this->innermostTableLevel) { $this->innermostTableLevel = $this->tableLevel; }
	if ($this->tableLevel > 1) {
		$this->table[$this->tableLevel][$this->tbctr[$this->tableLevel]]['nestedpos'] = array($this->row,$this->col,$this->tbctr[($this->tableLevel-1)]);
	}
	//++++++++++++++++++++++++++++

	$this->cell = array();
	$this->col=-1; //int
	$this->row=-1; //int
	$table = &$this->table[$this->tableLevel][$this->tbctr[$this->tableLevel]];
 
	// New table - any level
	if ($this->cacheTables) {
		$this->packTableData = true;	// required for cacheTables
		$this->simpleTables = false;  // Cannot co-exist with cacheTables
		$table['cache'] = _MPDF_TEMP_PATH.'_tempTblCache'.RAND(1,1000000).'.dat';
		$fh = fopen($table['cache'] , "wb") or $this->Error("When using cacheTables, you must have read/write access to cache files (".$table['cache'] .")");
		fwrite($fh, "\x00");
		fclose($fh);
		$table['ptr'] = 1 ;	// Must not be 0
	}

	$table['direction'] = $this->directionality;
	$table['bgcolor'] = false;
	$table['va'] = false;
	$table['txta'] = false;
	$table['topntail'] = false;
	$table['thead-underline'] = false;
	$table['border'] = false;
	$table['border_details']['R']['w'] = 0;
	$table['border_details']['L']['w'] = 0;
	$table['border_details']['T']['w'] = 0;
	$table['border_details']['B']['w'] = 0;
	$table['border_details']['R']['style'] = '';
	$table['border_details']['L']['style'] = '';
	$table['border_details']['T']['style'] = '';
	$table['border_details']['B']['style'] = '';
	$table['max_cell_border_width']['R'] = 0;
	$table['max_cell_border_width']['L'] = 0;
	$table['max_cell_border_width']['T'] = 0;
	$table['max_cell_border_width']['B'] = 0;
	$table['padding']['L'] = false;
	$table['padding']['R'] = false;
	$table['padding']['T'] = false;
	$table['padding']['B'] = false;
	$table['margin']['L'] = false;
	$table['margin']['R'] = false;
	$table['margin']['T'] = false;
	$table['margin']['B'] = false;
	$table['a'] = false;
	$table['border_spacing_H'] = false;
	$table['border_spacing_V'] = false;

	$this->Reset();
	$this->InlineProperties = array();
	$this->spanlvl = 0;
	$table['nc'] = $table['nr'] = 0;
	$this->tablethead = 0;
	$this->tabletfoot = 0;
	$this->tabletheadjustfinished = false;


	if ($this->blockjustfinished && !count($this->textbuffer) && $this->y != $this->tMargin && $this->collapseBlockMargins && $this->tableLevel==1) { $lastbottommargin = $this->lastblockbottommargin; }
	else { $lastbottommargin = 0; }
	$this->lastblockbottommargin = 0;
	$this->blockjustfinished=false;

	if ($this->tableLevel==1) {
		$this->tableCJK = false;
		$this->table_lineheight = $this->normalLineheight;
		$table['headernrows'] = 0;
		$table['footernrows'] = 0;
		$this->base_table_properties = array();
	}

	// ADDED CSS FUNCIONS FOR TABLE
	if ($this->tbCSSlvl==1) {
		$properties = $this->MergeCSS('TOPTABLE',$tag,$attr);
	}
	else {
		$properties = $this->MergeCSS('TABLE',$tag,$attr);
	}
	$w = '';
	if (isset($properties['WIDTH'])) { $w = $properties['WIDTH']; }

	if(isset($properties['DIRECTION']) && $properties['DIRECTION']) { $table['direction'] = strtolower($properties['DIRECTION']); }
	if(isset($attr['DIR']) && $attr['DIR']) { $table['direction'] = strtolower($attr['DIR']); }
	else if (!isset($table['direction'])){ $table['direction'] = $this->blk[$this->blklvl]['direction']; }

	if (isset($properties['BACKGROUND-COLOR'])) { $table['bgcolor'][-1] = $properties['BACKGROUND-COLOR'];	}
	else if (isset($properties['BACKGROUND'])) { $table['bgcolor'][-1] = $properties['BACKGROUND'];	}
	if (isset($properties['VERTICAL-ALIGN'])) { $table['va'] = $align[strtolower($properties['VERTICAL-ALIGN'])]; }
	if (isset($properties['TEXT-ALIGN'])) { $table['txta'] = $align[strtolower($properties['TEXT-ALIGN'])]; }
	if (isset($properties['AUTOSIZE']) && $properties['AUTOSIZE'] && $this->tableLevel ==1)	{
		$this->shrink_this_table_to_fit = $properties['AUTOSIZE'];
		if ($this->shrink_this_table_to_fit < 1) { $this->shrink_this_table_to_fit = 0; }
	}
	if (isset($properties['ROTATE']) && $properties['ROTATE'] && $this->tableLevel ==1)	{
		$this->table_rotate = $properties['ROTATE'];
	}
	if (isset($properties['TOPNTAIL'])) { $table['topntail'] = $properties['TOPNTAIL']; }
	if (isset($properties['THEAD-UNDERLINE'])) { $table['thead-underline'] = $properties['THEAD-UNDERLINE']; }

	if (isset($properties['BORDER'])) {
		$bord = $this->border_details($properties['BORDER']);
		if ($bord['s']) {
			$table['border'] = _BORDER_ALL;
			$table['border_details']['R'] = $bord;
			$table['border_details']['L'] = $bord;
			$table['border_details']['T'] = $bord;
			$table['border_details']['B'] = $bord;
		}
	}
	if (isset($properties['BORDER-RIGHT'])) {
		$table['border_details']['R'] = $this->border_details($properties['BORDER-RIGHT']);
	  $this->setBorder($table['border'], _BORDER_RIGHT, $table['border_details']['R']['s']);
	}
	if (isset($properties['BORDER-LEFT'])) {
		$table['border_details']['L'] = $this->border_details($properties['BORDER-LEFT']);
	  $this->setBorder($table['border'], _BORDER_LEFT, $table['border_details']['L']['s']);
	}
	if (isset($properties['BORDER-BOTTOM'])) {
		$table['border_details']['B'] = $this->border_details($properties['BORDER-BOTTOM']);
		$this->setBorder($table['border'], _BORDER_BOTTOM, $table['border_details']['B']['s']);
	}
	if (isset($properties['BORDER-TOP'])) {
		$table['border_details']['T'] = $this->border_details($properties['BORDER-TOP']);
		$this->setBorder($table['border'], _BORDER_TOP, $table['border_details']['T']['s']);
	}
	if ($table['border']){
		  $this->table_border_css_set = 1;
	}
	else {
	  $this->table_border_css_set = 0;
	}

	if (isset($properties['FONT-FAMILY'])) {
		$this->default_font = $properties['FONT-FAMILY'];
		$this->SetFont($this->default_font,'',0,false);
	}
	$this->base_table_properties['FONT-FAMILY'] = $this->FontFamily;	// mPDF 5.4.10

	if (isset($properties['FONT-SIZE'])) {
	   // mPDF 5.4.10
	   if ($this->tableLevel>1) { $mmsize = $this->ConvertSize($properties['FONT-SIZE'], $this->base_table_properties['FONT-SIZE']); }
	   else { $mmsize = $this->ConvertSize($properties['FONT-SIZE'],$this->default_font_size/_MPDFK); }
	   if ($mmsize) {
		$this->default_font_size = $mmsize*(_MPDFK);
   		$this->SetFontSize($this->default_font_size,false);
	   }
	}
	$this->base_table_properties['FONT-SIZE'] = $this->FontSize.'mm';	// mPDF 5.4.10

	if (isset($properties['FONT-WEIGHT'])) {
		if (strtoupper($properties['FONT-WEIGHT']) == 'BOLD')	{ $this->base_table_properties['FONT-WEIGHT'] = 'BOLD'; }
	}
	if (isset($properties['FONT-STYLE'])) {
		if (strtoupper($properties['FONT-STYLE']) == 'ITALIC') { $this->base_table_properties['FONT-STYLE'] = 'ITALIC'; }
	}
	if (isset($properties['COLOR'])) {
		$this->base_table_properties['COLOR'] = $properties['COLOR'];
	}
	if (isset($properties['FONT-KERNING'])) {
		$this->base_table_properties['FONT-KERNING'] = $properties['FONT-KERNING'];
	}
	if (isset($properties['LETTER-SPACING'])) {
		$this->base_table_properties['LETTER-SPACING'] = $properties['LETTER-SPACING'];
	}
	if (isset($properties['WORD-SPACING'])) {
		$this->base_table_properties['WORD-SPACING'] = $properties['WORD-SPACING'];
	}

	if (isset($properties['PADDING-LEFT'])) {
		$table['padding']['L'] = $this->ConvertSize($properties['PADDING-LEFT'],$this->blk[$this->blklvl]['inner_width'],$this->FontSize,false);
	}
	if (isset($properties['PADDING-RIGHT'])) {
		$table['padding']['R'] = $this->ConvertSize($properties['PADDING-RIGHT'],$this->blk[$this->blklvl]['inner_width'],$this->FontSize,false);
	}
	if (isset($properties['PADDING-TOP'])) {
		$table['padding']['T'] = $this->ConvertSize($properties['PADDING-TOP'],$this->blk[$this->blklvl]['inner_width'],$this->FontSize,false);
	}
	if (isset($properties['PADDING-BOTTOM'])) {
		$table['padding']['B'] = $this->ConvertSize($properties['PADDING-BOTTOM'],$this->blk[$this->blklvl]['inner_width'],$this->FontSize,false);
	}

	if (isset($properties['MARGIN-TOP'])) {
		if ($lastbottommargin) {
			$tmp = $this->ConvertSize($properties['MARGIN-TOP'],$this->blk[$this->blklvl]['inner_width'],$this->FontSize,false);
			if ($tmp > $lastbottommargin) { $properties['MARGIN-TOP'] -= $lastbottommargin; }
			else { $properties['MARGIN-TOP'] = 0; }
		}
		$table['margin']['T'] = $this->ConvertSize($properties['MARGIN-TOP'],$this->blk[$this->blklvl]['inner_width'],$this->FontSize,false);
	}

	if (isset($properties['MARGIN-BOTTOM'])) {
		$table['margin']['B'] = $this->ConvertSize($properties['MARGIN-BOTTOM'],$this->blk[$this->blklvl]['inner_width'],$this->FontSize,false);
	}
	if (isset($properties['MARGIN-LEFT'])) {
		$table['margin']['L'] = $this->ConvertSize($properties['MARGIN-LEFT'],$this->blk[$this->blklvl]['inner_width'],$this->FontSize,false);
	}

	if (isset($properties['MARGIN-RIGHT'])) {
		$table['margin']['R'] = $this->ConvertSize($properties['MARGIN-RIGHT'],$this->blk[$this->blklvl]['inner_width'],$this->FontSize,false);
	}
	if (isset($properties['MARGIN-LEFT']) && isset($properties['MARGIN-RIGHT']) && strtolower($properties['MARGIN-LEFT'])=='auto' && strtolower($properties['MARGIN-RIGHT'])=='auto') {
		$table['a'] = 'C';
	}
	else if (isset($properties['MARGIN-LEFT']) && strtolower($properties['MARGIN-LEFT'])=='auto') {
		$table['a'] = 'R';
	}
	else if (isset($properties['MARGIN-RIGHT']) && strtolower($properties['MARGIN-RIGHT'])=='auto') {
		$table['a'] = 'L';
	}

	if (isset($properties['LINE-HEIGHT']) && $this->tableLevel==1) {
		$this->table_lineheight = $this->fixLineheight($properties['LINE-HEIGHT']);
		if (!$this->table_lineheight) { $this->table_lineheight = $this->normalLineheight; }
	}

	if (isset($properties['BORDER-COLLAPSE']) && strtoupper($properties['BORDER-COLLAPSE'])=='SEPARATE') {
		$table['borders_separate'] = true;
	}
	else {
		$table['borders_separate'] = false;
	}

	if (isset($properties['BORDER-SPACING-H'])) {
		$table['border_spacing_H'] = $this->ConvertSize($properties['BORDER-SPACING-H'],$this->blk[$this->blklvl]['inner_width'],$this->FontSize,false);
	}
	if (isset($properties['BORDER-SPACING-V'])) {
		$table['border_spacing_V'] = $this->ConvertSize($properties['BORDER-SPACING-V'],$this->blk[$this->blklvl]['inner_width'],$this->FontSize,false);
	}

	if (isset($properties['EMPTY-CELLS'])) {
		$table['empty_cells'] = strtolower($properties['EMPTY-CELLS']); 	// 'hide'  or 'show'
	}
	else { $table['empty_cells'] = ''; }

	if (isset($properties['PAGE-BREAK-INSIDE']) && strtoupper($properties['PAGE-BREAK-INSIDE'])=='AVOID' && $this->tableLevel==1 && !$this->writingHTMLfooter) {
		$this->table_keep_together = true;
	}
	else if ($this->tableLevel==1) {
		$this->table_keep_together = false;
	}
	if (isset($properties['PAGE-BREAK-AFTER']) && $this->tableLevel==1) {
		$table['page_break_after'] = strtoupper($properties['PAGE-BREAK-AFTER']);
	}


	if (isset($properties['OVERFLOW']))	{
		$table['overflow'] = strtolower($properties['OVERFLOW']); 	// 'hidden' 'wrap' or 'visible' or 'auto'
		if (($this->ColActive || $this->tableLevel>1) && $table['overflow']=='visible') { unset($table['overflow']); }
	}

	$properties = array();

	if (!$table['borders_separate']) { $table['border_spacing_H'] = $table['border_spacing_V'] = 0; }
	else if (isset($attr['CELLSPACING'])) {
		$table['border_spacing_H'] = $table['border_spacing_V'] = $this->ConvertSize($attr['CELLSPACING'],$this->blk[$this->blklvl]['inner_width']);
	}


	if (isset($attr['CELLPADDING'])) {
		$table['cell_padding'] = $attr['CELLPADDING'];
	}
	else {
		$table['cell_padding'] = false;
	}

	if (isset($attr['BORDER']) && $attr['BORDER']=='1') {	// mPDF 5.5.08
		$this->table_border_attr_set = 1;	// mPDF 5.5.08
		$bord = $this->border_details('#000000 1px solid');
		if ($bord['s']) {
			$table['border'] = _BORDER_ALL;
			$table['border_details']['R'] = $bord;
			$table['border_details']['L'] = $bord;
			$table['border_details']['T'] = $bord;
			$table['border_details']['B'] = $bord;
		}
	}
	else {
		$this->table_border_attr_set = 0;
	}
	if (isset($attr['ALIGN'])) { $table['a']	= $align[strtolower($attr['ALIGN'])]; }
	if (!$table['a']) {
		if ($table['direction'] == 'rtl' ) { $table['a'] = 'R'; }
		else { $table['a'] = 'L'; }
	}
	if (isset($attr['BGCOLOR'])) { $table['bgcolor'][-1]	= $attr['BGCOLOR']; }

	if (isset($attr['WIDTH']) && $attr['WIDTH']) { $w = $attr['WIDTH']; }
	if ($w) { // set here or earlier in $properties
		$maxwidth = $this->blk[$this->blklvl]['inner_width'];
		if ($table['borders_separate']) {
			$tblblw = $table['margin']['L'] + $table['margin']['R'] + $table['border_details']['L']['w']/2 + $table['border_details']['R']['w']/2;
		}
		else {
			$tblblw = $table['margin']['L'] + $table['margin']['R'] + $table['max_cell_border_width']['L']/2 + $table['max_cell_border_width']['R']/2;
		}
		if (strpos($w,'%') && $this->tableLevel == 1 && !$this->ignore_table_percents ) {
			// % needs to be of inner box without table margins etc.
			$maxwidth -= $tblblw ;
			$wmm = $this->ConvertSize($w,$maxwidth,$this->FontSize,false);
			$table['w'] = $wmm + $tblblw ;
		}
		if (strpos($w,'%') && $this->tableLevel > 1 && !$this->ignore_table_percents && $this->keep_table_proportions) {
			$table['wpercent'] = $w + 0; 	// makes 80% -> 80
		}
		if (!strpos($w,'%') && !$this->ignore_table_widths ) {
			$wmm = $this->ConvertSize($w,$this->blk[$this->blklvl]['inner_width'],$this->FontSize,false);
			$table['w'] = $wmm + $tblblw ;
		}
		if (!$this->keep_table_proportions) {
			if (isset($table['w']) && $table['w'] > $this->blk[$this->blklvl]['inner_width']) { $table['w'] = $this->blk[$this->blklvl]['inner_width']; }
		}
	}

	if (isset($attr['AUTOSIZE']) && $this->tableLevel==1)	{
		$this->shrink_this_table_to_fit = $attr['AUTOSIZE'];
		if ($this->shrink_this_table_to_fit < 1) { $this->shrink_this_table_to_fit = 1; }
	}
	if (isset($attr['ROTATE']) && $this->tableLevel==1)	{
		$this->table_rotate = $attr['ROTATE'];
	}

	//++++++++++++++++++++++++++++
	// keeping block together on one page
	// Autosize is now forced therefore keep block together disabled
	if ($this->keep_block_together) {
		$this->keep_block_together = 0;
		$this->printdivbuffer();
		$this->blk[$this->blklvl]['keep_block_together'] = 0;
	}
	if ($this->table_rotate) {
		$this->tbrot_Links = array();
		$this->tbrot_Annots = array();
		$this->tbrotForms = array();
		$this->tbrot_Reference = array();
		$this->tbrot_BMoutlines = array();
		$this->tbrot_toc = array();
	}

	if ($this->kwt) {
		if ($this->table_rotate) { $this->table_keep_together = true; }
		$this->kwt = false;
		$this->kwt_saved = true;
	}

	if ($this->tableLevel==1 && $this->useGraphs) {
		if (isset($attr['ID']) && $attr['ID']) { $this->currentGraphId = strtoupper($attr['ID']); }
		else { $this->currentGraphId = '0'; }
		$this->graphs[$this->currentGraphId] = array();
	}
	//++++++++++++++++++++++++++++
	$this->plainCell_properties = array();
	unset($table);
	break;

    case 'THEAD':
	$this->lastoptionaltag = $tag; // Save current HTML specified optional endtag
	$this->tbCSSlvl++;
	$this->tablethead = 1;
	$this->tabletfoot = 0;
	$properties = $this->MergeCSS('TABLE',$tag,$attr);
	if (isset($properties['FONT-WEIGHT'])) {
		if (strtoupper($properties['FONT-WEIGHT']) == 'BOLD')	{ $this->thead_font_weight = 'B'; }
		else { $this->thead_font_weight = ''; }
	}

	if (isset($properties['FONT-STYLE'])) {
		if (strtoupper($properties['FONT-STYLE']) == 'ITALIC') { $this->thead_font_style = 'I'; }
		else { $this->thead_font_style = ''; }
	}
	if (isset($properties['FONT-VARIANT'])) {
		if (strtoupper($properties['FONT-VARIANT']) == 'SMALL-CAPS') { $this->thead_font_smCaps = 'S'; }
		else { $this->thead_font_smCaps = ''; }
	}

	if (isset($properties['VERTICAL-ALIGN'])) {
		$this->thead_valign_default = $properties['VERTICAL-ALIGN'];
	}
	if (isset($properties['TEXT-ALIGN'])) {
		$this->thead_textalign_default = $properties['TEXT-ALIGN'];
	}
	$properties = array();
	break;

    case 'TFOOT':
	$this->lastoptionaltag = $tag; // Save current HTML specified optional endtag
	$this->tbCSSlvl++;
	$this->tabletfoot = 1;
	$this->tablethead = 0;
	$properties = $this->MergeCSS('TABLE',$tag,$attr);
	if (isset($properties['FONT-WEIGHT'])) {
		if (strtoupper($properties['FONT-WEIGHT']) == 'BOLD')	{ $this->tfoot_font_weight = 'B'; }
		else { $this->tfoot_font_weight = ''; }
	}

	if (isset($properties['FONT-STYLE'])) {
		if (strtoupper($properties['FONT-STYLE']) == 'ITALIC') { $this->tfoot_font_style = 'I'; }
		else { $this->tfoot_font_style = ''; }
	}
	if (isset($properties['FONT-VARIANT'])) {
		if (strtoupper($properties['FONT-VARIANT']) == 'SMALL-CAPS') { $this->tfoot_font_smCaps = 'S'; }
		else { $this->tfoot_font_smCaps = ''; }
	}

	if (isset($properties['VERTICAL-ALIGN'])) {
		$this->tfoot_valign_default = $properties['VERTICAL-ALIGN'];
	}
	if (isset($properties['TEXT-ALIGN'])) {
		$this->tfoot_textalign_default = $properties['TEXT-ALIGN'];
	}
	$properties = array();
	break;


    case 'TBODY':
	$this->tablethead = 0;
	$this->tabletfoot = 0;
	$this->lastoptionaltag = $tag; // Save current HTML specified optional endtag
	$this->tbCSSlvl++;
	$this->MergeCSS('TABLE',$tag,$attr);
	break;


    case 'TR':
	$this->lastoptionaltag = $tag; // Save current HTML specified optional endtag
	$this->tbCSSlvl++;
	$this->row++;
	$this->table[$this->tableLevel][$this->tbctr[$this->tableLevel]]['nr']++;
	$this->col = -1;
	$properties = $this->MergeCSS('TABLE',$tag,$attr);

	if (!$this->simpleTables && (!isset($this->table[$this->tableLevel][$this->tbctr[$this->tableLevel]]['borders_separate']) || !$this->table[$this->tableLevel][$this->tbctr[$this->tableLevel]]['borders_separate'])) {
		if (isset($properties['BORDER-LEFT']) && $properties['BORDER-LEFT']) { $this->table[$this->tableLevel][$this->tbctr[$this->tableLevel]]['trborder-left'][$this->row] = $properties['BORDER-LEFT']; }
		if (isset($properties['BORDER-RIGHT']) && $properties['BORDER-RIGHT']) { $this->table[$this->tableLevel][$this->tbctr[$this->tableLevel]]['trborder-right'][$this->row] = $properties['BORDER-RIGHT']; }
		if (isset($properties['BORDER-TOP']) && $properties['BORDER-TOP']) { $this->table[$this->tableLevel][$this->tbctr[$this->tableLevel]]['trborder-top'][$this->row] = $properties['BORDER-TOP']; }
		if (isset($properties['BORDER-BOTTOM']) && $properties['BORDER-BOTTOM']) { $this->table[$this->tableLevel][$this->tbctr[$this->tableLevel]]['trborder-bottom'][$this->row] = $properties['BORDER-BOTTOM']; }
	}

	if (isset($properties['BACKGROUND-COLOR'])) { $this->table[$this->tableLevel][$this->tbctr[$this->tableLevel]]['bgcolor'][$this->row] = $properties['BACKGROUND-COLOR']; }





	if (isset($properties['TEXT-ROTATE'])) {
		$this->trow_text_rotate = $properties['TEXT-ROTATE'];
	}
	if (isset($attr['TEXT-ROTATE'])) $this->trow_text_rotate = $attr['TEXT-ROTATE'];

	if (isset($attr['BGCOLOR'])) $this->table[$this->tableLevel][$this->tbctr[$this->tableLevel]]['bgcolor'][$this->row] = $attr['BGCOLOR'];
	if ($this->tablethead) { $this->table[$this->tableLevel][$this->tbctr[$this->tableLevel]]['is_thead'][$this->row] = true; }
	if ($this->tabletfoot) { $this->table[$this->tableLevel][$this->tbctr[$this->tableLevel]]['is_tfoot'][$this->row] = true; }
	$properties = array();
	break;


    case 'TH':
    case 'TD':
	$this->ignorefollowingspaces = true;
	$this->lastoptionaltag = $tag; // Save current HTML specified optional endtag
	$this->tbCSSlvl++;
	$this->InlineProperties = array();
	$this->spanlvl = 0;
	$this->tdbegin = true;
	$this->col++;
	while (isset($this->cell[$this->row][$this->col])) { $this->col++; }

	//Update number column
	if ($this->table[$this->tableLevel][$this->tbctr[$this->tableLevel]]['nc'] < $this->col+1) { $this->table[$this->tableLevel][$this->tbctr[$this->tableLevel]]['nc'] = $this->col+1; }

	$table = &$this->table[$this->tableLevel][$this->tbctr[$this->tableLevel]];

	$c = array('a' => false,
	'R' => false,
	'nowrap' => false,
	'bgcolor' => false,
	'padding' => array('L' => false,
		'R' => false,
		'T' => false,
		'B' => false
		)
	);

	if ($this->simpleTables && $this->row==0 && $this->col==0){
		$table['simple']['border'] = false;
		$table['simple']['border_details']['R']['w'] = 0;
		$table['simple']['border_details']['L']['w'] = 0;
		$table['simple']['border_details']['T']['w'] = 0;
		$table['simple']['border_details']['B']['w'] = 0;
		$table['simple']['border_details']['R']['style'] = '';
		$table['simple']['border_details']['L']['style'] = '';
		$table['simple']['border_details']['T']['style'] = '';
		$table['simple']['border_details']['B']['style'] = '';
	}
	else if (!$this->simpleTables) {
	$c['border'] = false;
	$c['border_details']['R']['w'] = 0;
	$c['border_details']['L']['w'] = 0;
	$c['border_details']['T']['w'] = 0;
	$c['border_details']['B']['w'] = 0;
	$c['border_details']['mbw']['BL'] = 0;
	$c['border_details']['mbw']['BR'] = 0;
	$c['border_details']['mbw']['RT'] = 0;
	$c['border_details']['mbw']['RB'] = 0;
	$c['border_details']['mbw']['TL'] = 0;
	$c['border_details']['mbw']['TR'] = 0;
	$c['border_details']['mbw']['LT'] = 0;
	$c['border_details']['mbw']['LB'] = 0;
	$c['border_details']['R']['style'] = '';
	$c['border_details']['L']['style'] = '';
	$c['border_details']['T']['style'] = '';
	$c['border_details']['B']['style'] = '';
	$c['border_details']['R']['s'] = 0;
	$c['border_details']['L']['s'] = 0;
	$c['border_details']['T']['s'] = 0;
	$c['border_details']['B']['s'] = 0;
	$c['border_details']['R']['c'] = $this->ConvertColor(0);
	$c['border_details']['L']['c'] = $this->ConvertColor(0);
	$c['border_details']['T']['c'] = $this->ConvertColor(0);
	$c['border_details']['B']['c'] = $this->ConvertColor(0);
	$c['border_details']['R']['dom'] = 0;
	$c['border_details']['L']['dom'] = 0;
	$c['border_details']['T']['dom'] = 0;
	$c['border_details']['B']['dom'] = 0;
	}


	if ($table['va']) { $c['va'] = $table['va']; }
	if ($table['txta']) { $c['a'] = $table['txta']; }
	if ($this->table_border_attr_set) {
	  if ($table['border_details']) {
	    if (!$this->simpleTables){
		$c['border_details']['R'] = $table['border_details']['R'];
		$c['border_details']['L'] = $table['border_details']['L'];
		$c['border_details']['T'] = $table['border_details']['T'];
		$c['border_details']['B'] = $table['border_details']['B'];
		$c['border'] = $table['border'];
		$c['border_details']['L']['dom'] = 1;
		$c['border_details']['R']['dom'] = 1;
		$c['border_details']['T']['dom'] = 1;
		$c['border_details']['B']['dom'] = 1;
	    }
	    else if ($this->simpleTables && $this->row==0 && $this->col==0){
		$table['simple']['border_details']['R'] = $table['border_details']['R'];
		$table['simple']['border_details']['L'] = $table['border_details']['L'];
		$table['simple']['border_details']['T'] = $table['border_details']['T'];
		$table['simple']['border_details']['B'] = $table['border_details']['B'];
		$table['simple']['border'] = $table['border'];
	    }
	  }
	}
	// INHERITED THEAD CSS Properties
	if ($this->tablethead) {
		if ($this->thead_valign_default) $c['va'] = $align[strtolower($this->thead_valign_default)];
		if ($this->thead_textalign_default) $c['a'] = $align[strtolower($this->thead_textalign_default)];
		if ($this->thead_font_weight == 'B') { $this->SetStyle('B',true); }
		if ($this->thead_font_style == 'I') { $this->SetStyle('I',true); }
		if ($this->thead_font_smCaps == 'S') { $this->SetStyle('S',true); }
	}

	// INHERITED TFOOT CSS Properties
	if ($this->tabletfoot) {
		if ($this->tfoot_valign_default) $c['va'] = $align[strtolower($this->tfoot_valign_default)];
		if ($this->tfoot_textalign_default) $c['a'] = $align[strtolower($this->tfoot_textalign_default)];
		if ($this->tfoot_font_weight == 'B') { $this->SetStyle('B',true); }
		if ($this->tfoot_font_style == 'I') { $this->SetStyle('I',true); }
		if ($this->tfoot_font_style == 'S') { $this->SetStyle('S',true); }
	}


	if ($this->trow_text_rotate){
		$c['R'] = $this->trow_text_rotate;
	}

	$this->cell_border_dominance_L = 0;
	$this->cell_border_dominance_R = 0;
	$this->cell_border_dominance_T = 0;
	$this->cell_border_dominance_B = 0;

	$properties = $this->MergeCSS('TABLE',$tag,$attr);
	$properties = $this->array_merge_recursive_unique($this->base_table_properties, $properties);

	if (isset($properties['FONT-KERNING']) && (strtoupper($properties['FONT-KERNING'])=='NORMAL' || strtoupper($properties['FONT-KERNING'])=='AUTO')) {
		$this->kerning = true;
	}
	else { $this->kerning = false; }

	if (isset($properties['LETTER-SPACING']) && ($properties['LETTER-SPACING'] || $properties['LETTER-SPACING']==='0') && strtoupper($properties['LETTER-SPACING']) != 'NORMAL') {
		$this->lSpacingCSS = strtoupper($properties['LETTER-SPACING']);
		$this->fixedlSpacing = $this->ConvertSize($this->lSpacingCSS,$this->FontSize);
	}
	else {
		$this->lSpacingCSS = '';
		$this->fixedlSpacing = false;
	}
	if (isset($properties['WORD-SPACING']) && strtoupper($properties['WORD-SPACING']) != 'NORMAL') {
		$this->wSpacingCSS = strtoupper($properties['WORD-SPACING']);
		$this->minwSpacing = $this->ConvertSize($this->wSpacingCSS,$this->FontSize);
	}
	else {
		$this->minwSpacing = 0;
		$this->wSpacingCSS = '';
	}

	if (isset($properties['BACKGROUND-COLOR'])) { $c['bgcolor'] = $properties['BACKGROUND-COLOR']; }
	else if (isset($properties['BACKGROUND'])) { $c['bgcolor'] = $properties['BACKGROUND']; }


	if (isset($properties['VERTICAL-ALIGN'])) { $c['va']=$align[strtolower($properties['VERTICAL-ALIGN'])]; }
	if (isset($properties['TEXT-ALIGN'])) { $c['a'] = $align[strtolower($properties['TEXT-ALIGN'])]; }

	if (isset($properties['TEXT-ROTATE']) && ($properties['TEXT-ROTATE'] || $properties['TEXT-ROTATE']==="0")){
		$c['R'] = $properties['TEXT-ROTATE'];
	}
	if (isset($properties['BORDER'])) {
		$bord = $this->border_details($properties['BORDER']);
		if ($bord['s']) {
		   if (!$this->simpleTables){
			$c['border'] = _BORDER_ALL;
			$c['border_details']['R'] = $bord;
			$c['border_details']['L'] = $bord;
			$c['border_details']['T'] = $bord;
			$c['border_details']['B'] = $bord;
			$c['border_details']['L']['dom'] = $this->cell_border_dominance_L;
			$c['border_details']['R']['dom'] = $this->cell_border_dominance_R;
			$c['border_details']['T']['dom'] = $this->cell_border_dominance_T;
			$c['border_details']['B']['dom'] = $this->cell_border_dominance_B;
		   }
		   else if ($this->simpleTables && $this->row==0 && $this->col==0){
			$table['simple']['border'] = _BORDER_ALL;
			$table['simple']['border_details']['R'] = $bord;
			$table['simple']['border_details']['L'] = $bord;
			$table['simple']['border_details']['T'] = $bord;
			$table['simple']['border_details']['B'] = $bord;
		   }
		}
	}
	if (!$this->simpleTables){
	   if (isset($properties['BORDER-RIGHT']) && $properties['BORDER-RIGHT']) {
		$c['border_details']['R'] = $this->border_details($properties['BORDER-RIGHT']);
		$this->setBorder($c['border'], _BORDER_RIGHT, $c['border_details']['R']['s']);
		$c['border_details']['R']['dom'] = $this->cell_border_dominance_R;
	   }
	   if (isset($properties['BORDER-LEFT']) && $properties['BORDER-LEFT']) {
		$c['border_details']['L'] = $this->border_details($properties['BORDER-LEFT']);
		$this->setBorder($c['border'], _BORDER_LEFT, $c['border_details']['L']['s']);
		$c['border_details']['L']['dom'] = $this->cell_border_dominance_L;
	   }
	   if (isset($properties['BORDER-BOTTOM']) && $properties['BORDER-BOTTOM']) {
		$c['border_details']['B'] = $this->border_details($properties['BORDER-BOTTOM']);
		$this->setBorder($c['border'], _BORDER_BOTTOM, $c['border_details']['B']['s']);
		$c['border_details']['B']['dom'] = $this->cell_border_dominance_B;
	   }
	   if (isset($properties['BORDER-TOP']) && $properties['BORDER-TOP']) {
		$c['border_details']['T'] = $this->border_details($properties['BORDER-TOP']);
		$this->setBorder($c['border'], _BORDER_TOP, $c['border_details']['T']['s']);
		$c['border_details']['T']['dom'] = $this->cell_border_dominance_T;
	   }
	}
	else if ($this->simpleTables && $this->row==0 && $this->col==0){
	   if (isset($properties['BORDER-LEFT']) && $properties['BORDER-LEFT']) {
		$bord = $this->border_details($properties['BORDER-LEFT']);
			if ($bord['s']) { $table['simple']['border'] = _BORDER_ALL; }
			else { $table['simple']['border'] = 0; }
			$table['simple']['border_details']['R'] = $bord;
			$table['simple']['border_details']['L'] = $bord;
			$table['simple']['border_details']['T'] = $bord;
			$table['simple']['border_details']['B'] = $bord;
	   }
	}

	if ($this->simpleTables && $this->row==0 && $this->col==0 && !$table['borders_separate'] && $table['simple']['border'] ){
		$table['border_details'] = $table['simple']['border_details'];
		$table['border'] = $table['simple']['border'];
	}

	// Border set on TR (if collapsed only)
	if (!$table['borders_separate'] && !$this->simpleTables && isset($table['trborder-left'][$this->row])) {
		if ($this->col==0) {
			$left = $this->border_details($table['trborder-left'][$this->row]);
			$c['border_details']['L'] = $left;
			$this->setBorder($c['border'], _BORDER_LEFT, $c['border_details']['L']['s']);
		}
		$c['border_details']['B'] = $this->border_details($table['trborder-bottom'][$this->row]);
		$this->setBorder($c['border'], _BORDER_BOTTOM, $c['border_details']['B']['s']);
		$c['border_details']['T'] = $this->border_details($table['trborder-top'][$this->row]);
		$this->setBorder($c['border'], _BORDER_TOP, $c['border_details']['T']['s']);
	}

	if ($this->packTableData && !$this->simpleTables) {
		$c['borderbin'] = $this->_packCellBorder($c);
		unset($c['border']);
		unset($c['border_details']);
	}

	if (isset($properties['PADDING-LEFT'])) {
		$c['padding']['L'] = $this->ConvertSize($properties['PADDING-LEFT'],$this->blk[$this->blklvl]['inner_width'],$this->FontSize,false);
	}
	if (isset($properties['PADDING-RIGHT'])) {
		$c['padding']['R'] = $this->ConvertSize($properties['PADDING-RIGHT'],$this->blk[$this->blklvl]['inner_width'],$this->FontSize,false);
	}
	if (isset($properties['PADDING-BOTTOM'])) {
		$c['padding']['B'] = $this->ConvertSize($properties['PADDING-BOTTOM'],$this->blk[$this->blklvl]['inner_width'],$this->FontSize,false);
	}
	if (isset($properties['PADDING-TOP'])) {
		$c['padding']['T'] = $this->ConvertSize($properties['PADDING-TOP'],$this->blk[$this->blklvl]['inner_width'],$this->FontSize,false);
	}

	$w = '';
	if (isset($properties['WIDTH'])) { $w = $properties['WIDTH']; }
	if (isset($attr['WIDTH'])) { $w = $attr['WIDTH']; }
	if ($w) {
		if (strpos($w,'%') && !$this->ignore_table_percents ) { $c['wpercent'] = $w + 0; }	// makes 80% -> 80
		else if (!strpos($w,'%') && !$this->ignore_table_widths ) { $c['w'] = $this->ConvertSize($w,$this->blk[$this->blklvl]['inner_width'],$this->FontSize,false); }
	}

	if (isset($properties['HEIGHT']) && !strpos($properties['HEIGHT'],'%')) { $c['h'] = $this->ConvertSize($properties['HEIGHT'],$this->blk[$this->blklvl]['inner_width'],$this->FontSize,false); }

	if (isset($properties['COLOR'])) {
	  $cor = $this->ConvertColor($properties['COLOR']);
	  if ($cor) {
		$this->colorarray = $cor;
		$this->SetTColor($cor);
	  }
	}
	if (isset($properties['FONT-FAMILY'])) {
		$this->SetFont($properties['FONT-FAMILY'],'',0,false);
	}
	if (isset($properties['FONT-SIZE'])) {
	   $mmsize = $this->ConvertSize($properties['FONT-SIZE'],$this->default_font_size/_MPDFK);
	   if ($mmsize) {
  		$this->SetFontSize($mmsize*(_MPDFK),false);
	   }
	}
	$c['dfs'] = $this->FontSize;	// Default Font size
	if (isset($properties['FONT-WEIGHT'])) {
		if (strtoupper($properties['FONT-WEIGHT']) == 'BOLD')	{ $this->SetStyle('B',true); }
	}
	if (isset($properties['FONT-STYLE'])) {
		if (strtoupper($properties['FONT-STYLE']) == 'ITALIC') { $this->SetStyle('I',true); }
	}
	if (isset($properties['FONT-VARIANT'])) {
		if (strtoupper($properties['FONT-VARIANT']) == 'SMALL-CAPS') { $this->SetStyle('S',true); }
	}
	if (isset($properties['TEXT-DECORATION'])) {
		if (strtoupper($properties['TEXT-DECORATION']) == 'LINE-THROUGH') { $this->strike = true; }
		else if (strtoupper($properties['TEXT-DECORATION']) == 'UNDERLINE') { $this->SetStyle('U',true); }
	}
	if (isset($properties['TEXT-SHADOW'])) {
		$ts = $this->setCSStextshadow($properties['TEXT-SHADOW']);
		if ($ts) { $this->textshadow = $ts; }
	}
	if (isset($properties['TEXT-TRANSFORM'])) {
		if (strtoupper($properties['TEXT-TRANSFORM']) == 'CAPITALIZE') { $this->capitalize = true; }
		else if (strtoupper($properties['TEXT-TRANSFORM']) == 'UPPERCASE') { $this->toupper = true; }
		else if (strtoupper($properties['TEXT-TRANSFORM']) == 'LOWERCASE') { $this->tolower = true; }
	}
	if (isset($properties['WHITE-SPACE'])) {
		if (strtoupper($properties['WHITE-SPACE']) == 'NOWRAP') { $c['nowrap']= 1; }
	}
	$properties = array();


	if (isset($attr['HEIGHT']) && !strpos($attr['HEIGHT'],'%')) $c['h'] = $this->ConvertSize($attr['HEIGHT'],$this->blk[$this->blklvl]['inner_width'],$this->FontSize,false);

	if (isset($attr['ALIGN'])) $c['a'] = $align[strtolower($attr['ALIGN'])];

	if (!$c['a']) {
		if (isset($table['direction']) && $table['direction'] == 'rtl' ) { $c['a'] = 'R'; }
		else { $c['a'] = 'L'; }
	}
	if (isset($attr['VALIGN'])) $c['va'] = $align[strtolower($attr['VALIGN'])];


	if (isset($attr['BGCOLOR'])) $c['bgcolor'] = $attr['BGCOLOR'];
	if (isset($attr['TEXT-ROTATE'])) {
		$c['R'] = $attr['TEXT-ROTATE'];
	}
	if (isset($attr['NOWRAP']) && $attr['NOWRAP']) $c['nowrap']= 1;

	$this->cell[$this->row][$this->col] = $c;
	unset($c);
	$this->cell[$this->row][$this->col]['s'] = 0 ;

	$cs = $rs = 1;
	if (isset($attr['COLSPAN']) && $attr['COLSPAN']>1)	$cs = $this->cell[$this->row][$this->col]['colspan']	= $attr['COLSPAN'];
	if ($this->table[$this->tableLevel][$this->tbctr[$this->tableLevel]]['nc'] < $this->col+$cs) {
		$this->table[$this->tableLevel][$this->tbctr[$this->tableLevel]]['nc'] = $this->col+$cs;
	}	// following code moved outside if...
	for($l=$this->col; $l < $this->col+$cs ;$l++) {
		if ($l-$this->col) $this->cell[$this->row][$l] = 0;
	}
	if (isset($attr['ROWSPAN']) && $attr['ROWSPAN']>1)	$rs = $this->cell[$this->row][$this->col]['rowspan']	= $attr['ROWSPAN'];
	for ($k=$this->row ; $k < $this->row+$rs ;$k++) {
		for($l=$this->col; $l < $this->col+$cs ;$l++) {
			if ($k-$this->row || $l-$this->col)	$this->cell[$k][$l] = 0;
		}
	}
	unset($table);
	break;


    // *********** LISTS ********************
    case 'OL':
    case 'UL':
	$this->listjustfinished = false;

	if ($this->blockjustfinished && !count($this->textbuffer) && $this->y != $this->tMargin && $this->collapseBlockMargins) { $lastbottommargin = $this->lastblockbottommargin; }
	else { $lastbottommargin = 0; }
	$this->lastblockbottommargin = 0;
	$this->blockjustfinished=false;

	$this->linebreakjustfinished=false;
	$this->lastoptionaltag = ''; // Save current HTML specified optional endtag
	$this->listCSSlvl++;
	if((!$this->tableLevel) && ($this->listlvl == 0)) {
		$blockstate = 0;
		//if ($this->lastblocklevelchange == 1) { $blockstate = -1; }	// Top margins/padding only
		//else if ($this->lastblocklevelchange < 1) { $blockstate = 0; }	// NO margins/padding
		// called from block after new div e.g. <div> ... <ol> ...    Outputs block top margin/border and padding
		if (count($this->textbuffer) == 0 && $this->lastblocklevelchange == 1 && !$this->tableLevel && !$this->kwt) {
			$this->newFlowingBlock( $this->blk[$this->blklvl]['width'],$this->lineheight,'',false,false,1,true, $this->blk[$this->blklvl]['direction']);
			$this->finishFlowingBlock(true);	// true = END of flowing block
		}
		else if (count($this->textbuffer)) { $this->printbuffer($this->textbuffer,$blockstate); }
		$this->textbuffer=array();
		$this->lastblocklevelchange = -1;
	}
	// ol and ul types are mixed here
	if ($this->listlvl == 0) {
		$this->list_indent = array();
		$this->list_align = array();
		$this->list_lineheight = array();
		$this->InlineProperties['LIST'] = array();
		$this->InlineProperties['LISTITEM'] = array();
	}

	// A simple list for inside a table
	if($this->tableLevel) {
		$this->list_indent[$this->listlvl] = 0;	// mm default indent for each level
		if ($tag == 'OL') $this->listtype = '1';
		else if ($tag == 'UL') $this->listtype = 'disc';
      	if ($this->listlvl > 0) {
			$this->listlist[$this->listlvl]['MAXNUM'] = $this->listnum; //save previous lvl's maxnum
		}
		$this->listlvl++;
		$this->listnum = 0; // reset
		$this->listlist[$this->listlvl] = array('TYPE'=>$this->listtype,'MAXNUM'=>$this->listnum);
		break;
	}


	if (($this->PDFA || $this->PDFX) && $tag == 'UL') {
		if (($this->PDFA && !$this->PDFAauto) || ($this->PDFX && !$this->PDFXauto)) { $this->PDFAXwarnings[] = "List bullets cannot use core font Zapfdingbats in PDFA1-b or PDFX/1-a. (Substitute characters from current font used if available, otherwise substitutes hyphen '-')"; }
	}

	if ($this->listCSSlvl==1) {
		$properties = $this->MergeCSS('TOPLIST',$tag,$attr);
	}
	else {
		$properties = $this->MergeCSS('LIST',$tag,$attr);
	}
	if (!empty($properties)) $this->setCSS($properties,'LIST');
	// List-type

	$this->listtype = '';
	if (isset($attr['TYPE']) && $attr['TYPE']) { $this->listtype = $attr['TYPE']; }
	else if (isset($properties['LIST-STYLE-TYPE'])) {
		$this->listtype = $this->_getListStyle($properties['LIST-STYLE-TYPE']);
	}
	else if (isset($properties['LIST-STYLE'])) {
		$this->listtype = $this->_getListStyle($properties['LIST-STYLE']);
	}
	if (!$this->listtype) {
		if ($tag == 'OL') $this->listtype = '1';
		if ($tag == 'UL') {
			if ($this->listlvl % 3 == 0) $this->listtype = 'disc';
			elseif ($this->listlvl % 3 == 1) $this->listtype = 'circle';
			else $this->listtype = 'square';
		}
	}
      if ($this->listlvl == 0) {
	  $this->inherit_lineheight = 0;
	  $this->listlvl++; // first depth level
	  $this->listnum = 0; // reset
	  $this->listDir = (isset($this->blk[$this->blklvl]['direction']) ? $this->blk[$this->blklvl]['direction'] : null);
	  $occur = $this->listoccur[$this->listlvl] = 1;
	  $this->listlist[$this->listlvl][1] = array('TYPE'=>$this->listtype,'MAXNUM'=>$this->listnum);
      }
      else {
	if (!empty($this->textbuffer))
        {
		$this->listitem[] = array($this->listlvl,$this->listnum,$this->textbuffer,$this->listoccur[$this->listlvl],$this->listitemtype);
		$this->listnum++;
        }
	  // Save current lineheight to inherit
	  $this->textbuffer = array();
  	  $occur = $this->listoccur[$this->listlvl];
	  $this->listlist[$this->listlvl][$occur]['MAXNUM'] = $this->listnum; //save previous lvl's maxnum
	  $this->listlvl++;
	  $this->listnum = 0; // reset


	  if (!isset($this->listoccur[$this->listlvl]) || $this->listoccur[$this->listlvl] == 0) $this->listoccur[$this->listlvl] = 1;
	  else $this->listoccur[$this->listlvl]++;
  	  $occur = $this->listoccur[$this->listlvl];
	  $this->listlist[$this->listlvl][$occur] = array('TYPE'=>$this->listtype,'MAXNUM'=>$this->listnum);
      }


	// TOP LEVEL ONLY
	if ($this->listlvl == 1) {
	   if (isset($properties['MARGIN-TOP'])) {
		if ($lastbottommargin) {
			$tmp = $this->ConvertSize($properties['MARGIN-TOP'],$this->blk[$this->blklvl]['inner_width'],$this->FontSize,false);
			if ($tmp > $lastbottommargin) { $properties['MARGIN-TOP'] -= $lastbottommargin; }
			else { $properties['MARGIN-TOP'] = 0; }
		}
		$this->DivLn($this->ConvertSize($properties['MARGIN-TOP'],$this->blk[$this->blklvl]['inner_width'],$this->FontSize,false),$this->blklvl,true,1); 	// collapsible
	   }
	   if (isset($properties['MARGIN-BOTTOM'])) {
		$this->list_margin_bottom = $this->ConvertSize($properties['MARGIN-BOTTOM'],$this->blk[$this->blklvl]['inner_width'],$this->FontSize,false);
	   }
	   
	   if (isset($this->blk[$this->blklvl]['line_height'])) {
		$this->list_lineheight[$this->listlvl][$occur] = $this->blk[$this->blklvl]['line_height'];
	   }

	   if (isset($properties['DIRECTION']) && $properties['DIRECTION']) { $this->listDir = strtolower($properties['DIRECTION']); }
	   if (isset($attr['DIR']) && $attr['DIR']) { $this->listDir = strtolower($attr['DIR']); }

	}
	$this->list_indent[$this->listlvl][$occur] = 5;	// mm default indent for each level
	if (isset($properties['TEXT-INDENT'])) { $this->list_indent[$this->listlvl][$occur] = $this->ConvertSize($properties['TEXT-INDENT'],$this->blk[$this->blklvl]['inner_width'],$this->FontSize,false); }

	if (isset($properties['TEXT-ALIGN'])) {
		$this->list_align[$this->listlvl][$occur] = $align[strtolower($properties['TEXT-ALIGN'])];
	}


	if (isset($properties['LINE-HEIGHT'])) {
		$this->list_lineheight[$this->listlvl][$occur] = $this->fixLineheight($properties['LINE-HEIGHT']);
	}
	else if ($this->listlvl>1 && isset($this->list_lineheight[($this->listlvl - 1)][1])) {
		$this->list_lineheight[$this->listlvl][$occur] = end($this->list_lineheight[($this->listlvl - 1)]);
	}
	if (!isset($this->list_lineheight[$this->listlvl][$occur]) || !$this->list_lineheight[$this->listlvl][$occur]) {
		$this->list_lineheight[$this->listlvl][$occur] = $this->normalLineheight;
	}

	$this->InlineProperties['LIST'][$this->listlvl][$occur] = $this->saveInlineProperties();
	$properties = array();
     break;



    case 'LI':
	// Start Block
	$this->lastoptionaltag = $tag; // Save current HTML specified optional endtag
      $this->ignorefollowingspaces = true; //Eliminate exceeding left-side spaces
	// A simple list for inside a table
	if($this->tableLevel) {
	   $this->blockjustfinished=false;

	   // If already something in the Cell
	   if ((isset($this->cell[$this->row][$this->col]['maxs']) && $this->cell[$this->row][$this->col]['maxs'] > 0 ) || $this->cell[$this->row][$this->col]['s'] > 0 ) {
			$this->_saveCellTextBuffer("\n");
			if (!isset($this->cell[$this->row][$this->col]['maxs'])) {
				$this->cell[$this->row][$this->col]['maxs'] = $this->cell[$this->row][$this->col]['s'];
			}
			elseif($this->cell[$this->row][$this->col]['maxs'] < $this->cell[$this->row][$this->col]['s']) {
				$this->cell[$this->row][$this->col]['maxs'] = $this->cell[$this->row][$this->col]['s'];
			}
			$this->cell[$this->row][$this->col]['s'] = 0 ;
		}
		if ($this->listlvl == 0) { //in case of malformed HTML code. Example:(...)</p><li>Content</li><p>Paragraph1</p>(...)
			$this->listlvl++; // first depth level
			$this->listnum = 0; // reset
			$this->listlist[$this->listlvl] = array('TYPE'=>'disc','MAXNUM'=>$this->listnum);
		}

		$this->listnum++;
		switch($this->listlist[$this->listlvl]['TYPE']) {
		case 'A':
			$blt = $this->dec2alpha($this->listnum,true).$this->list_number_suffix;
			break;
		case 'a':
			$blt = $this->dec2alpha($this->listnum,false).$this->list_number_suffix;
			break;
		case 'I':
			$blt = $this->dec2roman($this->listnum,true).$this->list_number_suffix;
			break;
		case 'i':
			$blt = $this->dec2roman($this->listnum,false).$this->list_number_suffix;
			break;
		case '1':
			$blt = $this->listnum.$this->list_number_suffix;
            	break;
		default:
			if ($this->listlvl % 3 == 1 && $this->_charDefined($this->CurrentFont['cw'],8226)) { $blt = "\xe2\x80\xa2"; } 	// &#8226;
			else if ($this->listlvl % 3 == 2 && $this->_charDefined($this->CurrentFont['cw'],9900)) { $blt = "\xe2\x9a\xac"; } // &#9900;
			else if ($this->listlvl % 3 == 0 && $this->_charDefined($this->CurrentFont['cw'],9642)) { $blt = "\xe2\x96\xaa"; } // &#9642;
			else { $blt = '-'; }
			break;
		}

		// change to &nbsp; spaces
		if ($this->usingCoreFont) {
			$ls = str_repeat(chr(160).chr(160),($this->listlvl-1)*2) . $blt . ' ';
		}
		else {
			$ls = str_repeat("\xc2\xa0\xc2\xa0",($this->listlvl-1)*2) . $blt . ' ';
		}

		$this->_saveCellTextBuffer($ls, $this->HREF);
		$this->cell[$this->row][$this->col]['s'] += $this->GetStringWidth($ls);
		break;
	}
	//Observation: </LI> is ignored
	if ($this->listlvl == 0) { //in case of malformed HTML code. Example:(...)</p><li>Content</li><p>Paragraph1</p>(...)
	//First of all, skip a line
		$this->listlvl++; // first depth level
		$this->listnum = 0; // reset
		$this->listoccur[$this->listlvl] = 1;
		$this->listlist[$this->listlvl][1] = array('TYPE'=>'disc','MAXNUM'=>$this->listnum);
	}
	if ($this->listnum == 0) {
		$this->listnum++;
		$this->textbuffer = array();
	}
	else {
		if (!empty($this->textbuffer)) {
		  if (!$this->listjustfinished) {
			$this->listitem[] = array($this->listlvl,$this->listnum,$this->textbuffer,$this->listoccur[$this->listlvl],$this->listitemtype);
			$this->listnum++;
		  }
		  else {
			$this->listitem[] = array($this->listlvl,$this->listnum,$this->textbuffer,$this->listoccur[$this->listlvl],$this->listitemtype, true);
		  }
		}
		$this->textbuffer = array();
      }
	$this->listjustfinished = false;

	$this->listCSSlvl++;
	$properties = $this->MergeCSS('LIST',$tag,$attr);
	if (!empty($properties)) $this->setCSS($properties,'LIST');
	$this->InlineProperties['LISTITEM'][$this->listlvl][$this->listoccur[$this->listlvl]][$this->listnum] = $this->saveInlineProperties();

	// List-type
	if (isset($attr['TYPE']) && $attr['TYPE']) { $this->listitemtype = $attr['TYPE']; }
	else if (isset($properties['LIST-STYLE-TYPE'])) {
		$this->listitemtype = $this->_getListStyle($properties['LIST-STYLE-TYPE']);
	}
	else if (isset($properties['LIST-STYLE'])) {
		$this->listitemtype = $this->_getListStyle($properties['LIST-STYLE']);
	}
	else $this->listitemtype = '';
      break;

  }//end of switch
}


function _getListStyle($ls) {
 	if (stristr($ls,'decimal')) { return '1'; }
/*  CSS3 list-styles numeric (selected) + I added tamil
arabic-indic | bengali | devanagari | gujarati | gurmukhi | kannada | malayalam | oriya | persian | telugu | thai | urdu
*/
	else if (preg_match('/(disc|circle|square|arabic-indic|bengali|devanagari|gujarati|gurmukhi|kannada|malayalam|oriya|persian|tamil|telugu|thai|urdu)/i',$ls,$m)) {
		return strtolower(trim($m[1]));
	}
	else if (stristr($ls,'lower-roman')) { return 'i'; }
	else if (stristr($ls,'upper-roman')) { return 'I'; }
	else if (stristr($ls,'lower-latin')|| stristr($ls,'lower-alpha')) { return 'a'; }
	else if (stristr($ls,'upper-latin') || stristr($ls,'upper-alpha')) { return 'A'; }
	else if (stristr($ls,'none')) { return 'none'; }
	else if (preg_match('/U\+([a-fA-F0-9]+)/i',$ls)) { return $ls; }
	else { return ''; }
}



function CloseTag($tag)
{
	$this->ignorefollowingspaces = false; //Eliminate exceeding left-side spaces
    //Closing tag
    if($tag=='OPTION') { $this->selectoption['ACTIVE'] = false; 	$this->lastoptionaltag = ''; }

    if($tag=='TTS' or $tag=='TTA' or $tag=='TTZ') {
	if ($this->InlineProperties[$tag]) { $this->restoreInlineProperties($this->InlineProperties[$tag]); }
	unset($this->InlineProperties[$tag]);
	$ltag = strtolower($tag);
	$this->$ltag = false;
    }


    if($tag=='FONT' || $tag=='SPAN' || $tag=='CODE' || $tag=='KBD' || $tag=='SAMP' || $tag=='TT' || $tag=='VAR'
	|| $tag=='INS' || $tag=='STRONG' || $tag=='CITE' || $tag=='SUB' || $tag=='SUP' || $tag=='S' || $tag=='STRIKE' || $tag=='DEL'
	|| $tag=='Q' || $tag=='EM' || $tag=='B' || $tag=='I' || $tag=='U' | $tag=='SMALL' || $tag=='BIG' || $tag=='ACRONYM'
	|| $tag=='MARK'  || $tag=='TIME'  || $tag=='PROGRESS'  || $tag=='METER'
	) {	// mPDF 5.5.09

	if ($tag == 'SPAN') {
		if (isset($this->InlineProperties['SPAN'][$this->spanlvl]) && $this->InlineProperties['SPAN'][$this->spanlvl]) { $this->restoreInlineProperties($this->InlineProperties['SPAN'][$this->spanlvl]); }
		unset($this->InlineProperties['SPAN'][$this->spanlvl]);
		$this->spanlvl--;
	}
	else {
		if (isset($this->InlineProperties[$tag]) && $this->InlineProperties[$tag]) { $this->restoreInlineProperties($this->InlineProperties[$tag]); }
		unset($this->InlineProperties[$tag]);
	}

    }

    if($tag=='METER' || $tag=='PROGRESS') {
	$this->inMeter = false;	// mPDF 5.5.09
    }


    if($tag=='A') {
	$this->HREF='';
	if (isset($this->InlineProperties['A'])) { $this->restoreInlineProperties($this->InlineProperties['A']); }
	unset($this->InlineProperties['A']);
    }

    if($tag=='LEGEND') {	// mPDF 5.4.18
	if (count($this->textbuffer) && !$this->tableLevel) {
		$leg = $this->textbuffer[(count($this->textbuffer)-1)];
		unset($this->textbuffer[(count($this->textbuffer)-1)]);
		$this->textbuffer = array_values($this->textbuffer);
		$this->blk[$this->blklvl]['border_legend'] = $leg;
		$this->blk[$this->blklvl]['margin_top'] += ($leg[11]/2)/_MPDFK;
		$this->blk[$this->blklvl]['padding_top'] += ($leg[11]/2)/_MPDFK;
	}
	if (isset($this->InlineProperties['LEGEND'])) { $this->restoreInlineProperties($this->InlineProperties['LEGEND']); }
	unset($this->InlineProperties['LEGEND']);
	$this->ignorefollowingspaces = true; //Eliminate exceeding left-side spaces
    }





	// *********** BLOCKS ********************
	// mPDF 5.4.18
    if($tag=='P' || $tag=='DIV' || $tag=='H1' || $tag=='H2' || $tag=='H3' || $tag=='H4' || $tag=='H5' || $tag=='H6' || $tag=='PRE'
	 || $tag=='FORM' || $tag=='ADDRESS' || $tag=='BLOCKQUOTE' || $tag=='CENTER' || $tag=='DT'  || $tag=='DD'  || $tag=='DL'
	|| $tag=='CAPTION' || $tag=='FIELDSET'
	|| $tag=='ARTICLE' || $tag=='ASIDE' || $tag=='FIGURE' || $tag=='FIGCAPTION' || $tag=='FOOTER' || $tag=='HEADER' || $tag=='HGROUP'
	|| $tag=='NAV' || $tag=='SECTION'  || $tag=='DETAILS' || $tag=='SUMMARY'
	) { 	// mPDF 5.5.09	// mPDF 5.5.22

	$this->ignorefollowingspaces = true; //Eliminate exceeding left-side spaces
	$this->blockjustfinished=true;

	$this->lastblockbottommargin = $this->blk[$this->blklvl]['margin_bottom'];
	if ($this->listlvl>0) { return; }

	if($this->tableLevel) {
		if ($this->linebreakjustfinished) { $this->blockjustfinished=false; }
		if (isset($this->InlineProperties['BLOCKINTABLE'])) {
			if ($this->InlineProperties['BLOCKINTABLE']) { $this->restoreInlineProperties($this->InlineProperties['BLOCKINTABLE']); }
			unset($this->InlineProperties['BLOCKINTABLE']);
		}
		if($tag=='PRE') { $this->ispre=false; }
		return;
	}
	$this->lastoptionaltag = '';
	$this->divbegin=false;

	$this->linebreakjustfinished=false;

	$this->x = $this->lMargin + $this->blk[$this->blklvl]['outer_left_margin'];



	//Print content
	if ($this->lastblocklevelchange == 1) { $blockstate = 3; }	// Top & bottom margins/padding
	else if ($this->lastblocklevelchange == -1) { $blockstate = 2; }	// Bottom margins/padding only
	else { $blockstate = 0; }
	// called from after e.g. </table> </div> </div> ...    Outputs block margin/border and padding
	if (count($this->textbuffer) && $this->textbuffer[count($this->textbuffer)-1]) {
	  if (substr($this->textbuffer[count($this->textbuffer)-1][0],0,3) != "\xbb\xa4\xac") {	// not special content
	   if ($this->usingCoreFont) {
		$this->textbuffer[count($this->textbuffer)-1][0] = preg_replace('/[ ]+$/', '', $this->textbuffer[count($this->textbuffer)-1][0]);
	   }
	   else {
		$this->textbuffer[count($this->textbuffer)-1][0] = preg_replace('/[ ]+$/u', '', $this->textbuffer[count($this->textbuffer)-1][0]);	   }
	  }
	}

	if (count($this->textbuffer) == 0 && $this->lastblocklevelchange != 0) {
		//$this->newFlowingBlock( $this->blk[$this->blklvl]['width'],$this->lineheight,'',false,false,2,true, $this->blk[$this->blklvl]['direction']);
		$this->newFlowingBlock( $this->blk[$this->blklvl]['width'],$this->lineheight,'',false,false,$blockstate,true, $this->blk[$this->blklvl]['direction']);
		$this->finishFlowingBlock(true);	// true = END of flowing block
		$this->PaintDivBB('',$blockstate);
	}
	else {
		$this->printbuffer($this->textbuffer,$blockstate);
	}


	$this->textbuffer=array();

	if ($this->blk[$this->blklvl]['keep_block_together']) {
		$this->printdivbuffer();
	}

	if ($this->kwt) {
		$this->kwt_height = $this->y - $this->kwt_y0;
	}


	if($tag=='PRE') { $this->ispre=false; }


	if (isset($this->blk[$this->blklvl]['visibility']) && $this->blk[$this->blklvl]['visibility']!='visible') {
		$this->SetVisibility('visible');
	}

	if (isset($this->blk[$this->blklvl]['page_break_after'])) { $page_break_after = $this->blk[$this->blklvl]['page_break_after']; }
	else { $page_break_after = ''; }

	//Reset values
	$this->Reset();

	// mPDF 5.6.01  - LAYERS
	if (isset($this->blk[$this->blklvl]['z-index']) && $this->blk[$this->blklvl]['z-index'] > 0) {
		$this->EndLayer();
	}

	if ($this->blklvl > 0) {	// ==0 SHOULDN'T HAPPEN - NOT XHTML
	   if ($this->blk[$this->blklvl]['tag'] == $tag) {
		unset($this->blk[$this->blklvl]);
		$this->blklvl--;
	   }
	   //else { echo $tag; exit; }	// debug - forces error if incorrectly nested html tags
	}

	$this->lastblocklevelchange = -1 ;
	// Reset Inline-type properties
	if (isset($this->blk[$this->blklvl]['InlineProperties'])) { $this->restoreInlineProperties($this->blk[$this->blklvl]['InlineProperties']); }

	$this->x = $this->lMargin + $this->blk[$this->blklvl]['outer_left_margin'];

	if ($page_break_after) {
		$save_blklvl = $this->blklvl;
		$save_blk = $this->blk;
		$save_silp = $this->saveInlineProperties();
		$save_spanlvl = $this->spanlvl;
		$save_ilp = $this->InlineProperties;
		if ($this->blklvl>1) {
			// Close any open block tags
			for ($b= $this->blklvl;$b>0;$b--) { $this->CloseTag($this->blk[$b]['tag']); }
			// Output any text left in buffer
			if (count($this->textbuffer)) { $this->printbuffer($this->textbuffer); $this->textbuffer=array(); }
		}
		if ($page_break_after == 'RIGHT') { $this->AddPage($this->CurOrientation,'NEXT-ODD','','','','','', '','', '','','','','','',0,0,0,0,$pagesel); }
		else if ($page_break_after == 'LEFT') { $this->AddPage($this->CurOrientation,'NEXT-EVEN','','','','','', '','', '','','','','','',0,0,0,0,$pagesel); }
		else { $this->AddPage($this->CurOrientation,'','','','','','', '','', '','','','','','',0,0,0,0,$pagesel); }
		if (!$this->restoreBlockPagebreaks) {
			$this->blklvl = 0;
			$this->lastblocklevelchange = 0;
			$this->blk = array();
			$this->initialiseBlock($this->blk[0]);
			$this->blk[0]['width'] =& $this->pgwidth;
			$this->blk[0]['inner_width'] =& $this->pgwidth;
			$this->blk[0]['blockContext'] = $this->blockContext;
			$properties = $this->MergeCSS('BLOCK','BODY','');
			$this->setCSS($properties,'','BODY');
			$this->blklvl++;
			$currblk =& $this->blk[$this->blklvl];
			$prevblk =& $this->blk[$this->blklvl-1];

			$this->initialiseBlock($currblk);
			$currblk['tag'] = $tag;
			$currblk['attr'] = $attr;

			$this->Reset();
			$properties = $this->MergeCSS('BLOCK',$tag,$attr);
		}
		if ($this->restoreBlockPagebreaks && !$this->tableLevel && !$this->listlvl) {
			$this->blk = $save_blk;
			// Re-open block tags
			$t = $this->blk[0]['tag'];
			$a = $this->blk[0]['attr'];
			$this->blklvl = 0;
			for ($b=0; $b<=$save_blklvl;$b++) {
				$tc = $t;
				$ac = $a;
				$t = $this->blk[$b+1]['tag'];
				$a = $this->blk[$b+1]['attr'];
				unset($this->blk[$b+1]);
				$this->OpenTag($tc,$ac);
			}
			$this->spanlvl = $save_spanlvl;
			$this->InlineProperties = $save_ilp;
			$this->restoreInlineProperties($save_silp);
		}
	}

    }



    if($tag=='TH') $this->SetStyle('B',false);

    if(($tag=='TH' or $tag=='TD') && $this->tableLevel) {
	$this->lastoptionaltag = 'TR';
	unset($this->tablecascadeCSS[$this->tbCSSlvl]);
	$this->tbCSSlvl--;
	if (!$this->tdbegin) { return; }
	$this->tdbegin = false;
	// Added for correct calculation of cell column width - otherwise misses the last line if not end </p> etc.
	if (!isset($this->cell[$this->row][$this->col]['maxs'])) {
		if (!is_array($this->cell[$this->row][$this->col])) { $this->Error("You may have an error in your HTML code e.g. &lt;/td&gt;&lt;/td&gt;"); }
		$this->cell[$this->row][$this->col]['maxs'] = $this->cell[$this->row][$this->col]['s'];
	}
	elseif($this->cell[$this->row][$this->col]['maxs'] < $this->cell[$this->row][$this->col]['s']) {
		$this->cell[$this->row][$this->col]['maxs'] = $this->cell[$this->row][$this->col]['s'];
	}

	// Remove last <br> if at end of cell
	if (isset($this->cell[$this->row][$this->col]['textbuffer'])) { $ntb = count($this->cell[$this->row][$this->col]['textbuffer']); }
	else { $ntb = 0; }
	if ($ntb>1 && $this->cell[$this->row][$this->col]['textbuffer'][$ntb-1][0] == "\n") {
		unset($this->cell[$this->row][$this->col]['textbuffer'][$ntb-1]);
	}

	if ($this->cacheTables) {
		$clen = $this->_cacheCell($this->cell[$this->row][$this->col], $this->table[$this->tableLevel][$this->tbctr[$this->tableLevel]]['cache']);
		$this->cell[$this->row][$this->col] = $this->table[$this->tableLevel][$this->tbctr[$this->tableLevel]]['ptr'];
		$this->table[$this->tableLevel][$this->tbctr[$this->tableLevel]]['ptr'] += $clen;
	}

	if ($this->tablethead) {
		$this->table[$this->tableLevel][$this->tbctr[$this->tableLevel]]['is_thead'][$this->row] = true;
		if ($this->tableLevel==1) { $this->table[$this->tableLevel][$this->tbctr[$this->tableLevel]]['headernrows'] = max($this->table[$this->tableLevel][$this->tbctr[$this->tableLevel]]['headernrows'] , ($this->row+1)); }
	}
	if ($this->tabletfoot) {
		$this->table[$this->tableLevel][$this->tbctr[$this->tableLevel]]['is_tfoot'][$this->row] = true;
		if ($this->tableLevel==1) { $this->table[$this->tableLevel][$this->tbctr[$this->tableLevel]]['footernrows'] = max($this->table[$this->tableLevel][$this->tbctr[$this->tableLevel]]['footernrows'] , ($this->row+1 - $this->table[$this->tableLevel][$this->tbctr[$this->tableLevel]]['headernrows'] )); }
	}
	$this->Reset();
    }

    if($tag=='TR' && $this->tableLevel) {
	// If Border set on TR - Update right border
	if (isset($this->table[$this->tableLevel][$this->tbctr[$this->tableLevel]]['trborder-left'][$this->row])) {
		if ($this->cacheTables) {
			$c = $this->_uncacheCell($this->cell[$this->row][$this->col], $this->table[$this->tableLevel][$this->tbctr[$this->tableLevel]]['cache'], null);
		}
		else { $c =& $this->cell[$this->row][$this->col]; }
		if ($c) {
			if ($this->packTableData) {
				$cell = $this->_unpackCellBorder($c['borderbin'] );
			}
			else { $cell = $c; }
			$cell['border_details']['R'] = $this->border_details($this->table[$this->tableLevel][$this->tbctr[$this->tableLevel]]['trborder-right'][$this->row]);
			$this->setBorder($cell['border'], _BORDER_RIGHT, $cell['border_details']['R']['s']);
			if ($this->packTableData) {
				$c['borderbin'] = $this->_packCellBorder($cell);
				unset($c['border']);
				unset($c['border_details']);
			}
			else { $c = $cell; }
		}
		if ($this->cacheTables) {
			$fh = fopen($this->table[$this->tableLevel][$this->tbctr[$this->tableLevel]]['cache'], "r+b");
			$this->_cacheUpdateBorder($c, $fh, $this->cell[$this->row][$this->col]);
			fclose($fh);
		}
	}
	$this->lastoptionaltag = '';
	unset($this->tablecascadeCSS[$this->tbCSSlvl]);
	$this->tbCSSlvl--;
	$this->trow_text_rotate = '';
	$this->tabletheadjustfinished = false;
   }

    if($tag=='TBODY') {
	$this->lastoptionaltag = '';
	unset($this->tablecascadeCSS[$this->tbCSSlvl]);
	$this->tbCSSlvl--;
    }

    if($tag=='THEAD') {
	$this->lastoptionaltag = '';
	unset($this->tablecascadeCSS[$this->tbCSSlvl]);
	$this->tbCSSlvl--;
	$this->tablethead = 0;
	$this->tabletheadjustfinished = true;
	$this->ResetStyles();
	$this->thead_font_weight = '';
	$this->thead_font_style = '';
	$this->thead_font_smCaps = '';

	$this->thead_valign_default = '';
	$this->thead_textalign_default = '';
    }

    if($tag=='TFOOT') {
	$this->lastoptionaltag = '';
	unset($this->tablecascadeCSS[$this->tbCSSlvl]);
	$this->tbCSSlvl--;
	$this->tabletfoot = 0;
	$this->ResetStyles();
	$this->tfoot_font_weight = '';
	$this->tfoot_font_style = '';
	$this->tfoot_font_smCaps = '';

	$this->tfoot_valign_default = '';
	$this->tfoot_textalign_default = '';
    }

    if($tag=='TABLE') { // TABLE-END (
	$this->lastoptionaltag = '';
	unset($this->tablecascadeCSS[$this->tbCSSlvl]);
	$this->tbCSSlvl--;
	$this->ignorefollowingspaces = true; //Eliminate exceeding left-side spaces
	$this->table[$this->tableLevel][$this->tbctr[$this->tableLevel]]['cells'] = $this->cell;
	$this->table[$this->tableLevel][$this->tbctr[$this->tableLevel]]['wc'] = array_pad(array(),$this->table[$this->tableLevel][$this->tbctr[$this->tableLevel]]['nc'],array('miw'=>0,'maw'=>0));
	$this->table[$this->tableLevel][$this->tbctr[$this->tableLevel]]['hr'] = array_pad(array(),$this->table[$this->tableLevel][$this->tbctr[$this->tableLevel]]['nr'],0);

	// Move table footer <tfoot> row to end of table
	if (isset($this->table[$this->tableLevel][$this->tbctr[$this->tableLevel]]['is_tfoot']) && count($this->table[$this->tableLevel][$this->tbctr[$this->tableLevel]]['is_tfoot'])) {
		$tfrows = array();
		foreach($this->table[$this->tableLevel][$this->tbctr[$this->tableLevel]]['is_tfoot'] AS $r=>$val) {
			if ($val) { $tfrows[] = $r; }
		}
		$temp = array();
		$temptf = array();
		foreach($this->table[$this->tableLevel][$this->tbctr[$this->tableLevel]]['cells'] AS $k=>$row) {
			if (in_array($k,$tfrows)) {
				$temptf[] = $row;
			}
			else {
				$temp[] = $row;
			}
		}
		$this->table[$this->tableLevel][$this->tbctr[$this->tableLevel]]['is_tfoot'] = array();
		for($i=count($temp) ; $i<(count($temp)+count($temptf)); $i++) {
			$this->table[$this->tableLevel][$this->tbctr[$this->tableLevel]]['is_tfoot'][$i] = true;
		}
		// Update nestedpos row references
		if (count($this->table[($this->tableLevel+1)])) {
		  foreach($this->table[($this->tableLevel+1)] AS $nid=>$nested) {
			$this->table[($this->tableLevel+1)][$nid]['nestedpos'][0] -= count($temptf);
		  }
		}
		$this->table[$this->tableLevel][$this->tbctr[$this->tableLevel]]['cells'] = array_merge($temp, $temptf);

		// Update other arays set on row number
		// [trbackground-images] [trgradients]
		$temptrbgi = array();
		$temptrbgg = array();
		$temptrbgc = array();
		$temptrbgc[-1] = $this->table[$this->tableLevel][$this->tbctr[$this->tableLevel]]['bgcolor'][-1];
		for($k=0; $k<$this->table[$this->tableLevel][$this->tbctr[$this->tableLevel]]['nr']; $k++) {
			if (!in_array($k,$tfrows)) {
				$temptrbgi[] = $this->table[$this->tableLevel][$this->tbctr[$this->tableLevel]]['trbackground-images'][$k];
				$temptrbgg[] = $this->table[$this->tableLevel][$this->tbctr[$this->tableLevel]]['trgradients'][$k];
				$temptrbgc[] = $this->table[$this->tableLevel][$this->tbctr[$this->tableLevel]]['bgcolor'][$k];
			}
		}
		for($k=0; $k<$this->table[$this->tableLevel][$this->tbctr[$this->tableLevel]]['nr']; $k++) {
			if (in_array($k,$tfrows)) {
				$temptrbgi[] = $this->table[$this->tableLevel][$this->tbctr[$this->tableLevel]]['trbackground-images'][$k];
				$temptrbgg[] = $this->table[$this->tableLevel][$this->tbctr[$this->tableLevel]]['trgradients'][$k];
				$temptrbgc[] = $this->table[$this->tableLevel][$this->tbctr[$this->tableLevel]]['bgcolor'][$k];
			}
		}
		$this->table[$this->tableLevel][$this->tbctr[$this->tableLevel]]['trbackground-images'] = $temptrbgi;
		$this->table[$this->tableLevel][$this->tbctr[$this->tableLevel]]['trgradients'] = $temptrbgg;
		$this->table[$this->tableLevel][$this->tbctr[$this->tableLevel]]['bgcolor'] = $temptrbgc ;
		// Should Update all other arays set on row number, but cell properties have been set so not needed
		// [bgcolor] [trborder-left] [trborder-right] [trborder-top] [trborder-bottom]
	}

	if ($this->table[$this->tableLevel][$this->tbctr[$this->tableLevel]]['direction']=='rtl') {
		$this->_reverseTableDir($this->table[$this->tableLevel][$this->tbctr[$this->tableLevel]]);
	}

	// Fix Borders *********************************************
	$this->_fixTableBorders($this->table[$this->tableLevel][$this->tbctr[$this->tableLevel]]);


	if ($this->table_rotate <> 0) {
		$this->tablebuffer = '';
		// Max width for rotated table
		$this->tbrot_maxw = $this->h - ($this->y + $this->bMargin + 1);
		$this->tbrot_maxh = $this->blk[$this->blklvl]['inner_width'] ;		// Max width for rotated table
		$this->tbrot_align = $this->table[$this->tableLevel][$this->tbctr[$this->tableLevel]]['a'] ;
	}
	$this->shrin_k = 1;

	if ($this->shrink_tables_to_fit < 1) { $this->shrink_tables_to_fit = 1; }
	if (!$this->shrink_this_table_to_fit) { $this->shrink_this_table_to_fit = $this->shrink_tables_to_fit; }

	if ($this->tableLevel>1) {
		// deal with nested table

		$this->_tableColumnWidth($this->table[$this->tableLevel][$this->tbctr[$this->tableLevel]],true);

		$tmiw = $this->table[$this->tableLevel][$this->tbctr[$this->tableLevel]]['miw'];
		$tmaw = $this->table[$this->tableLevel][$this->tbctr[$this->tableLevel]]['maw'];
		$tl = $this->table[$this->tableLevel][$this->tbctr[$this->tableLevel]]['tl'];

		// Go down to lower table level
		$this->tableLevel--;

		// Reset lower level table
		$this->base_table_properties = $this->table[$this->tableLevel][$this->tbctr[$this->tableLevel]]['baseProperties'];
		$this->cell = $this->table[$this->tableLevel][$this->tbctr[$this->tableLevel]]['cells'];
		// mPDF 5.4.10
		if (isset($this->cell['PARENTCELL'])) {
			if ($this->cell['PARENTCELL']) { $this->restoreInlineProperties($this->cell['PARENTCELL']); }
			unset($this->cell['PARENTCELL']);
		}
		$this->row = $this->table[$this->tableLevel][$this->tbctr[$this->tableLevel]]['currrow'];
		$this->col = $this->table[$this->tableLevel][$this->tbctr[$this->tableLevel]]['currcol'];
		$objattr = array();
		$objattr['type'] = 'nestedtable';
		$objattr['nestedcontent'] = $this->tbctr[($this->tableLevel+1)];
		$objattr['table'] = $this->tbctr[$this->tableLevel];
		$objattr['row'] = $this->row;
		$objattr['col'] = $this->col;
		$objattr['level'] = $this->tableLevel;
		$e = "\xbb\xa4\xactype=nestedtable,objattr=".serialize($objattr)."\xbb\xa4\xac";
		$this->_saveCellTextBuffer($e);
		$this->cell[$this->row][$this->col]['s'] += $tl ;
		if (!isset($this->cell[$this->row][$this->col]['maxs'])) {
			$this->cell[$this->row][$this->col]['maxs'] = $this->cell[$this->row][$this->col]['s'];
		}
		elseif($this->cell[$this->row][$this->col]['maxs'] < $this->cell[$this->row][$this->col]['s']) {
			$this->cell[$this->row][$this->col]['maxs'] = $this->cell[$this->row][$this->col]['s'];
		}
		$this->cell[$this->row][$this->col]['s'] = 0;// reset
		if ((isset($this->cell[$this->row][$this->col]['nestedmaw']) && $this->cell[$this->row][$this->col]['nestedmaw'] < $tmaw) || !isset($this->cell[$this->row][$this->col]['nestedmaw'])) { $this->cell[$this->row][$this->col]['nestedmaw'] = $tmaw ; }
		if ((isset($this->cell[$this->row][$this->col]['nestedmiw']) && $this->cell[$this->row][$this->col]['nestedmiw'] < $tmiw) || !isset($this->cell[$this->row][$this->col]['nestedmiw'])) { $this->cell[$this->row][$this->col]['nestedmiw'] = $tmiw ; }
		$this->tdbegin = true;
		$this->nestedtablejustfinished = true;
		$this->ignorefollowingspaces = true;
		return;
	}
	$this->cMarginL = 0;
	$this->cMarginR = 0;
	$this->cMarginT = 0;
	$this->cMarginB = 0;
	$this->cellPaddingL = 0;
	$this->cellPaddingR = 0;
	$this->cellPaddingT = 0;
	$this->cellPaddingB = 0;

	if (isset($this->table[1][1]['overflow']) && $this->table[1][1]['overflow']=='visible') {
		if ($this->kwt || $this->table_rotate || $this->table_keep_together || $this->ColActive) {
			$this->kwt = false;
			$this->table_rotate = 0;
			$this->table_keep_together = false;
			//die("mPDF Warning: You cannot use CSS overflow:visible together with any of these functions: 'Keep-with-table', rotated tables, page-break-inside:avoid, or columns");
		}
		$this->_tableColumnWidth($this->table[1][1],true);
		$this->_tableWidth($this->table[1][1]);
	}
	else {
	  if (!$this->kwt_saved) { $this->kwt_height = 0; }

	  list($check,$tablemiw) = $this->_tableColumnWidth($this->table[1][1],true);
	  $save_table = $this->table;
	  if ($this->cacheTables) { $this->_backupCacheFiles(); }
	  $reset_to_minimum_width = false;
	  $added_page = false;

	  if ($check > 1) {
		if ($check > $this->shrink_this_table_to_fit && $this->table_rotate) {
			if ($this->y != $this->tMargin) {
				$this->AddPage($this->CurOrientation);
				$this->kwt_moved = true;
			}
			$added_page = true;
			$this->tbrot_maxw = $this->h - ($this->y + $this->bMargin + 5) - $this->kwt_height;
			//$check = $tablemiw/$this->tbrot_maxw; 	// undo any shrink
			$check = 1; 	// undo any shrink
		}
		$reset_to_minimum_width = true;
	  }

	  if ($reset_to_minimum_width) {

		$this->shrin_k = $check;

 		$this->default_font_size /= $this->shrin_k;
		$this->SetFontSize($this->default_font_size, false );

		$this->shrinkTable($this->table[1][1],$this->shrin_k);

		$this->_tableColumnWidth($this->table[1][1],false);	// repeat

		// Starting at $this->innermostTableLevel
		// Shrink table values - and redo columnWidth
		for($lvl=2;$lvl<=$this->innermostTableLevel;$lvl++) {
			for ($nid=1; $nid<=$this->tbctr[$lvl]; $nid++) {
				$this->shrinkTable($this->table[$lvl][$nid],$this->shrin_k);
				$this->_tableColumnWidth($this->table[$lvl][$nid],false);
			}
		}
	  }

	  // Set table cell widths for top level table
	  // Use $shrin_k to resize but don't change again
	  $this->SetLineHeight('',$this->table_lineheight);

	  // Top level table
	  $this->_tableWidth($this->table[1][1]);

	}


	// Now work through any nested tables setting child table[w'] = parent cell['w']
	// Now do nested tables _tableWidth
	for($lvl=2;$lvl<=$this->innermostTableLevel;$lvl++) {
		for ($nid=1; $nid<=$this->tbctr[$lvl]; $nid++) {
			// HERE set child table width = cell width

			list($parentrow, $parentcol, $parentnid) = $this->table[$lvl][$nid]['nestedpos'];

			if ($this->cacheTables) {
				$c = $this->_uncacheCell($this->table[($lvl-1)][$parentnid]['cells'][$parentrow][$parentcol], $this->table[($lvl-1)][$parentnid]['cache'], null);
			}
			else
				$c =& $this->table[($lvl-1)][$parentnid]['cells'][$parentrow][$parentcol];

			if (isset($c['colspan']) && $c['colspan']> 1) {
			   $parentwidth = 0;
			   for($cs=0;$cs<$c['colspan'] ; $cs++) {
				$parentwidth += $this->table[($lvl-1)][$parentnid]['wc'][$parentcol+$cs];
			   }
			}
			else { $parentwidth = $this->table[($lvl-1)][$parentnid]['wc'][$parentcol]; }


			//$parentwidth -= ALLOW FOR PADDING ETC.in parent cell
			if (!$this->simpleTables){
			 if ($this->packTableData) {
			 	list($bt,$br,$bb,$bl) = $this->_getBorderWidths($c['borderbin']);
			 }
			 else {
				$br = $c['border_details']['R']['w'];
				$bl = $c['border_details']['L']['w'];
			 }
			 if ($this->table[$lvl-1][$parentnid]['borders_separate']) {
			  $parentwidth -= $br + $bl
				+ $c['padding']['L']
				+ $c['padding']['R']
				+ $this->table[($lvl-1)][$parentnid]['border_spacing_H'];
			 }
			 else {
			  $parentwidth -= $br/2 + $bl/2
				+ $c['padding']['L']
				+ $c['padding']['R'];
			 }
			}
			else if ($this->simpleTables){
			 if ($this->table[$lvl-1][$parentnid]['borders_separate']) {
			  $parentwidth -= $this->table[($lvl-1)][$parentnid]['simple']['border_details']['L']['w']
				+ $this->table[($lvl-1)][$parentnid]['simple']['border_details']['R']['w']
				+ $c['padding']['L']
				+ $c['padding']['R']
				+ $this->table[($lvl-1)][$parentnid]['border_spacing_H'];
			 }
			 else {
			  $parentwidth -= $this->table[($lvl-1)][$parentnid]['simple']['border_details']['L']['w']/2
				+ $this->table[($lvl-1)][$parentnid]['simple']['border_details']['R']['w']/2
				+ $c['padding']['L']
				+ $c['padding']['R'];
			 }
			}
			if (isset($this->table[$lvl][$nid]['wpercent']) && $this->table[$lvl][$nid]['wpercent'] && $lvl>1) {
				$this->table[$lvl][$nid]['w'] = $parentwidth;
			}
			else if ($parentwidth > $this->table[$lvl][$nid]['maw']) {
				$this->table[$lvl][$nid]['w'] = $this->table[$lvl][$nid]['maw'];
			}
			else {
				$this->table[$lvl][$nid]['w'] = $parentwidth;
			}
			unset($c);
			$this->_tableWidth($this->table[$lvl][$nid]);
		}
	}

	// Starting at $this->innermostTableLevel
	// Cascade back up nested tables: setting heights back up the tree
	for($lvl=$this->innermostTableLevel;$lvl>0;$lvl--) {
		for ($nid=1; $nid<=$this->tbctr[$lvl]; $nid++) {
			list($tableheight,$maxrowheight,$fullpage,$remainingpage, $maxfirstrowheight) = $this->_tableHeight($this->table[$lvl][$nid]);			}
	}
	if ($this->table[1][1]['overflow']=='visible') {
		if ($maxrowheight > $fullpage) { die("mPDF Warning: A Table row is greater than available height. You cannot use CSS overflow:visible"); }
		if ($maxfirstrowheight > $remainingpage) { $this->AddPage($this->CurOrientation); }
		$r = 0; $c = 0; $p = 0; $y = 0;
		while (!$finished) {
			list($finished,$r,$c,$p,$y,$y0) = $this->_tableWrite($this->table[1][1],true,$r,$c,$p,$y);
			if (!$finished) {
				$this->AddPage($this->CurOrientation);
				// If printed something on first spread, set same y
				if ($r==0 && $y0 > -1) { $this->y = $y0; }
			}
		}
	}
	else {
	  $recalculate = 1;
	  $forcerecalc = false;
	  // RESIZING ALGORITHM
	  if ($maxrowheight > $fullpage) {
		$recalculate = $this->tbsqrt($maxrowheight / $fullpage, 1);
		$forcerecalc = true;
	  }
	  else if ($this->table_rotate) {	// NB $remainingpage == $fullpage == the width of the page
		if ($tableheight > $remainingpage) {
			// If can fit on remainder of page whilst respecting autsize value..
			if (($this->shrin_k * $this->tbsqrt($tableheight / $remainingpage, 1)) <= $this->shrink_this_table_to_fit) {
				$recalculate = $this->tbsqrt($tableheight / $remainingpage, 1);
			}
			else if (!$added_page) {
				if ($this->y != $this->tMargin) {
					$this->AddPage($this->CurOrientation);
					$this->kwt_moved = true;
				}
				$added_page = true;
				$this->tbrot_maxw = $this->h - ($this->y + $this->bMargin + 5) - $this->kwt_height;
				// 0.001 to force it to recalculate
				$recalculate = (1 / $this->shrin_k) + 0.001; 	// undo any shrink
			}
		}
		else { $recalculate = 1; }
	  }
	  else if ($this->table_keep_together || ($this->table[1][1]['nr']==1 && !$this->writingHTMLfooter)) {
		if ($tableheight > $fullpage) {
			if (($this->shrin_k * $this->tbsqrt($tableheight / $fullpage, 1)) <= $this->shrink_this_table_to_fit) {
				$recalculate = $this->tbsqrt($tableheight / $fullpage, 1);
			}
			else if ($this->tableMinSizePriority) {
				$this->table_keep_together = false;
				$recalculate = 1.001;
			}
			else {
				if ($this->y != $this->tMargin) {	// mPDF 5.1
					$this->AddPage($this->CurOrientation);
					$this->kwt_moved = true;
				}
				$added_page = true;
				$this->tbrot_maxw = $this->h - ($this->y + $this->bMargin + 5) - $this->kwt_height;
				$recalculate = $this->tbsqrt($tableheight / $fullpage, 1);
			}
		}
		else if ($tableheight > $remainingpage) {
			// If can fit on remainder of page whilst respecting autsize value..
			if (($this->shrin_k * $this->tbsqrt($tableheight / $remainingpage, 1)) <= $this->shrink_this_table_to_fit) {
				$recalculate = $this->tbsqrt($tableheight / $remainingpage, 1);
			}
			else {
				if ($this->y != $this->tMargin) {
					$this->AddPage($this->CurOrientation);
					$this->kwt_moved = true;
				}
				$added_page = true;
				$this->tbrot_maxw = $this->h - ($this->y + $this->bMargin + 5) - $this->kwt_height;
				$recalculate = 1.001;
			}
		}
		else { $recalculate = 1; }
	  }
	  else { $recalculate = 1; }

	  if ($recalculate > $this->shrink_this_table_to_fit && !$forcerecalc) { $recalculate = $this->shrink_this_table_to_fit; }

	  $iteration = 1;

	  // RECALCULATE
	  while($recalculate <> 1) {
		$this->shrin_k1 = $recalculate ;
		$this->shrin_k *= $recalculate ;
 		$this->default_font_size /= ($this->shrin_k1) ;
		$this->SetFontSize($this->default_font_size, false );
		$this->SetLineHeight('',$this->table_lineheight);
		$this->table = $save_table;
		if ($this->cacheTables) { $this->_restoreCacheFiles(); }
		if ($this->shrin_k <> 1) { $this->shrinkTable($this->table[1][1],$this->shrin_k); }
		$this->_tableColumnWidth($this->table[1][1],false);	// repeat

		// Starting at $this->innermostTableLevel
		// Shrink table values - and redo columnWidth
		for($lvl=2;$lvl<=$this->innermostTableLevel;$lvl++) {
			for ($nid=1; $nid<=$this->tbctr[$lvl]; $nid++) {
				if ($this->shrin_k <> 1) { $this->shrinkTable($this->table[$lvl][$nid],$this->shrin_k); }
				$this->_tableColumnWidth($this->table[$lvl][$nid],false);
			}
		}
		// Set table cell widths for top level table

		// Top level table
		$this->_tableWidth($this->table[1][1]);

		// Now work through any nested tables setting child table[w'] = parent cell['w']
		// Now do nested tables _tableWidth
		for($lvl=2;$lvl<=$this->innermostTableLevel;$lvl++) {
			for ($nid=1; $nid<=$this->tbctr[$lvl]; $nid++) {
				// HERE set child table width = cell width

				list($parentrow, $parentcol, $parentnid) = $this->table[$lvl][$nid]['nestedpos'];
				if ($this->cacheTables) {
					$c = $this->_uncacheCell($this->table[($lvl-1)][$parentnid]['cells'][$parentrow][$parentcol], $this->table[($lvl-1)][$parentnid]['cache'], null);
				}
				else
					$c =& $this->table[($lvl-1)][$parentnid]['cells'][$parentrow][$parentcol];

				if (isset($c['colspan']) && $c['colspan']> 1) {
				   $parentwidth = 0;
				   for($cs=0;$cs<$c['colspan'] ; $cs++) {
					$parentwidth += $this->table[($lvl-1)][$parentnid]['wc'][$parentcol+$cs];
				   }
				}
				else { $parentwidth = $this->table[($lvl-1)][$parentnid]['wc'][$parentcol]; }

				//$parentwidth -= ALLOW FOR PADDING ETC.in parent cell
				if (!$this->simpleTables){
				 if ($this->packTableData) {
				 	list($bt,$br,$bb,$bl) = $this->_getBorderWidths($c['borderbin']);
				 }
				 else {
					$br = $c['border_details']['R']['w'];
					$bl = $c['border_details']['L']['w'];
				 }
				 if ($this->table[$lvl-1][$parentnid]['borders_separate']) {
				  $parentwidth -= $br + $bl
					+ $c['padding']['L']
					+ $c['padding']['R']
					+ $this->table[($lvl-1)][$parentnid]['border_spacing_H'];
				 }
				 else {
				  $parentwidth -= $br/2 + $bl/2
					+ $c['padding']['L']
					+ $c['padding']['R'];
				 }
				}
				else if ($this->simpleTables){
				 if ($this->table[$lvl-1][$parentnid]['borders_separate']) {
				  $parentwidth -= $this->table[($lvl-1)][$parentnid]['simple']['border_details']['L']['w']
					+ $this->table[($lvl-1)][$parentnid]['simple']['border_details']['R']['w']
					+ $c['padding']['L']
					+ $c['padding']['R']
					+ $this->table[($lvl-1)][$parentnid]['border_spacing_H'];
				 }
				 else {
				  $parentwidth -= ($this->table[($lvl-1)][$parentnid]['simple']['border_details']['L']['w']
					+ $this->table[($lvl-1)][$parentnid]['simple']['border_details']['R']['w']) /2
					+ $c['padding']['L']
					+ $c['padding']['R'];
				 }
				}
				if (isset($this->table[$lvl][$nid]['wpercent']) && $this->table[$lvl][$nid]['wpercent'] && $lvl>1) {
					$this->table[$lvl][$nid]['w'] = $parentwidth;
				}
				else if ($parentwidth > $this->table[$lvl][$nid]['maw']) {
					$this->table[$lvl][$nid]['w'] = $this->table[$lvl][$nid]['maw'] ;
				}
				else {
					$this->table[$lvl][$nid]['w'] = $parentwidth;
				}
				unset($c);
				$this->_tableWidth($this->table[$lvl][$nid]);
			}
		}

		// Starting at $this->innermostTableLevel
		// Cascade back up nested tables: setting heights back up the tree
		for($lvl=$this->innermostTableLevel;$lvl>0;$lvl--) {
			for ($nid=1; $nid<=$this->tbctr[$lvl]; $nid++) {
				list($tableheight,$maxrowheight,$fullpage,$remainingpage, $maxfirstrowheight) = $this->_tableHeight($this->table[$lvl][$nid]);			}
		}

		// RESIZING ALGORITHM

		if ($maxrowheight > $fullpage) { $recalculate = $this->tbsqrt($maxrowheight / $fullpage, $iteration); $iteration++; }
		else if ($this->table_rotate && $tableheight > $remainingpage && !$added_page) {
			// If can fit on remainder of page whilst respecting autosize value..
			if (($this->shrin_k * $this->tbsqrt($tableheight / $remainingpage, $iteration)) <= $this->shrink_this_table_to_fit) {
				$recalculate = $this->tbsqrt($tableheight / $remainingpage, $iteration); $iteration++;
			}
			else {
				if (!$added_page) {
					$this->AddPage($this->CurOrientation);
					$added_page = true;
					$this->kwt_moved = true;
					$this->tbrot_maxw = $this->h - ($this->y + $this->bMargin + 5) - $this->kwt_height;
				}
				// 0.001 to force it to recalculate
				$recalculate = (1 / $this->shrin_k) + 0.001; 	// undo any shrink
			}
		}
		else if ($this->table_keep_together || ($this->table[1][1]['nr']==1 && !$this->writingHTMLfooter)) {
			if ($tableheight > $fullpage) {
				if (($this->shrin_k * $this->tbsqrt($tableheight / $fullpage, $iteration)) <= $this->shrink_this_table_to_fit) {
					$recalculate = $this->tbsqrt($tableheight / $fullpage, $iteration); $iteration++;
				}
				else if ($this->tableMinSizePriority) {
					$this->table_keep_together = false;
					$recalculate = (1 / $this->shrin_k) + 0.001;
				}
				else {
				   if (!$added_page && $this->y != $this->tMargin) {
					$this->AddPage($this->CurOrientation);
					$added_page = true;
					$this->kwt_moved = true;
					$this->tbrot_maxw = $this->h - ($this->y + $this->bMargin + 5) - $this->kwt_height;
				   }
				   $recalculate = $this->tbsqrt($tableheight / $fullpage, $iteration); $iteration++;
				}
			}
			else if ($tableheight > $remainingpage) {
				// If can fit on remainder of page whilst respecting autosize value..
				if (($this->shrin_k * $this->tbsqrt($tableheight / $remainingpage, $iteration)) <= $this->shrink_this_table_to_fit) {
					$recalculate = $this->tbsqrt($tableheight / $remainingpage, $iteration);  $iteration++;
				}
				else {
					if (!$added_page) {
						$this->AddPage($this->CurOrientation);
						$added_page = true;
						$this->kwt_moved = true;
						$this->tbrot_maxw = $this->h - ($this->y + $this->bMargin + 5) - $this->kwt_height;
					}
				 
					//$recalculate = $this->tbsqrt($tableheight / $fullpage, $iteration); $iteration++;
					$recalculate = (1 / $this->shrin_k) + 0.001; 	// undo any shrink
				}
			}
			else { $recalculate = 1; }
		}
		else { $recalculate = 1; }
	  }


	  if ($maxfirstrowheight > $remainingpage && !$added_page && !$this->table_rotate && !$this->ColActive && !$this->table_keep_together && !$this->writingHTMLheader && !$this->writingHTMLfooter) {
		$this->AddPage($this->CurOrientation);
		$this->kwt_moved = true;
	  }

	  // keep-with-table: if page has advanced, print out buffer now, else done in fn. _Tablewrite()
	  if ($this->kwt_saved && $this->kwt_moved) {
		$this->printkwtbuffer();
		$this->kwt_moved = false;
		$this->kwt_saved = false;
	  }

	  // Recursively writes all tables starting at top level
	  $this->_tableWrite($this->table[1][1]);

	  if ($this->table_rotate && $this->tablebuffer) {
		$this->PageBreakTrigger=$this->h-$this->bMargin;
		$save_tr = $this->table_rotate;
		$save_y = $this->y;
		$this->table_rotate = 0;
		$this->y = $this->tbrot_y0;
		$h = 	$this->tbrot_w;
		$this->DivLn($h,$this->blklvl,true);

		$this->table_rotate = $save_tr;
		$this->y = $save_y;

		$this->printtablebuffer();
	  }
	  $this->table_rotate = 0;
	}


	$this->x = $this->lMargin + $this->blk[$this->blklvl]['outer_left_margin'];

	$this->maxPosR = max($this->maxPosR , ($this->x + $this->table[1][1]['w']));

	$this->blockjustfinished=true;
	$this->lastblockbottommargin = $this->table[1][1]['margin']['B'];
	//Reset values

	if (isset($this->table[1][1]['page_break_after'])) { $page_break_after = $this->table[1][1]['page_break_after']; }
	else { $page_break_after = ''; }

	// Keep-with-table
	$this->kwt = false;
	$this->kwt_y0 = 0;
	$this->kwt_x0 = 0;
	$this->kwt_height = 0;
	$this->kwt_buffer = array();
	$this->kwt_Links = array();
	$this->kwt_Annots = array();
	$this->kwt_moved = false;
	$this->kwt_saved = false;

	$this->kwt_Reference = array();
	$this->kwt_BMoutlines = array();
	$this->kwt_toc = array();

	$this->shrin_k = 1;
	$this->shrink_this_table_to_fit = 0;

	unset($this->table);
	$this->table=array(); //array
	$this->tableLevel=0;
	$this->tbctr=array();
	$this->innermostTableLevel=0;
	$this->tbCSSlvl = 0;
	$this->tablecascadeCSS = array();

	unset($this->cell);
	$this->cell=array(); //array

	$this->col=-1; //int
	$this->row=-1; //int
	$this->Reset();

 	$this->cellPaddingL = 0;
 	$this->cellPaddingT = 0;
 	$this->cellPaddingR = 0;
 	$this->cellPaddingB = 0;
 	$this->cMarginL = 0;
 	$this->cMarginT = 0;
 	$this->cMarginR = 0;
 	$this->cMarginB = 0;
 	$this->default_font_size = $this->original_default_font_size;
	$this->default_font = $this->original_default_font;
   	$this->SetFontSize($this->default_font_size, false);
	$this->SetFont($this->default_font,'',0,false);
	$this->SetLineHeight();
	if (isset($this->blk[$this->blklvl]['InlineProperties'])) { $this->restoreInlineProperties($this->blk[$this->blklvl]['InlineProperties']);}

	if ($page_break_after) {
		$save_blklvl = $this->blklvl;
		$save_blk = $this->blk;
		$save_silp = $this->saveInlineProperties();
		$save_spanlvl = $this->spanlvl;
		$save_ilp = $this->InlineProperties;
		if ($this->blklvl>1) {
			// Close any open block tags
			for ($b= $this->blklvl;$b>0;$b--) { $this->CloseTag($this->blk[$b]['tag']); }
			// Output any text left in buffer
			if (count($this->textbuffer)) { $this->printbuffer($this->textbuffer); $this->textbuffer=array(); }
		}
		if ($page_break_after == 'RIGHT') { $this->AddPage($this->CurOrientation,'NEXT-ODD','','','','','', '','', '','','','','','',0,0,0,0,$pagesel); }
		else if ($page_break_after == 'LEFT') { $this->AddPage($this->CurOrientation,'NEXT-EVEN','','','','','', '','', '','','','','','',0,0,0,0,$pagesel); }
		else { $this->AddPage($this->CurOrientation,'','','','','','', '','', '','','','','','',0,0,0,0,$pagesel); }
		if (!$this->restoreBlockPagebreaks) {
			$this->blklvl = 0;
			$this->lastblocklevelchange = 0;
			$this->blk = array();
			$this->initialiseBlock($this->blk[0]);
			$this->blk[0]['width'] =& $this->pgwidth;
			$this->blk[0]['inner_width'] =& $this->pgwidth;
			$this->blk[0]['blockContext'] = $this->blockContext;
			$properties = $this->MergeCSS('BLOCK','BODY','');
			$this->setCSS($properties,'','BODY');
		}

		if ($this->restoreBlockPagebreaks) {
			$this->blk = $save_blk;
			// Re-open block tags
			$t = $this->blk[0]['tag'];
			$a = $this->blk[0]['attr'];
			$this->blklvl = 0;
			for ($b=0; $b<=$save_blklvl;$b++) {
				$tc = $t;
				$ac = $a;
				$t = $this->blk[$b+1]['tag'];
				$a = $this->blk[$b+1]['attr'];
				unset($this->blk[$b+1]);
				$this->OpenTag($tc,$ac);
			}
			$this->spanlvl = $save_spanlvl;
			$this->InlineProperties = $save_ilp;
			$this->restoreInlineProperties($save_silp);
		}
	}

    }

	// *********** LISTS ********************

    if($tag=='LI') {
	$this->lastoptionaltag = '';
	unset($this->listcascadeCSS[$this->listCSSlvl]);
	$this->listCSSlvl--;
	if (isset($this->listoccur[$this->listlvl]) && isset($this->InlineProperties['LIST'][$this->listlvl][$this->listoccur[$this->listlvl]])) { $this->restoreInlineProperties($this->InlineProperties['LIST'][$this->listlvl][$this->listoccur[$this->listlvl]]); }
    }


    if(($tag=='UL') or ($tag=='OL')) {
      $this->ignorefollowingspaces = true; //Eliminate exceeding left-side spaces
	unset($this->listcascadeCSS[$this->listCSSlvl]);
	$this->listCSSlvl--;

	$this->lastoptionaltag = '';
	// A simple list for inside a table
	if($this->tableLevel) {
		$this->listlist[$this->listlvl]['MAXNUM'] = $this->listnum; //save previous lvl's maxnum
		unset($this->listlist[$this->listlvl]);
		$this->listlvl--;
		if (isset($this->listlist[$this->listlvl]['MAXNUM'])) { $this->listnum = $this->listlist[$this->listlvl]['MAXNUM']; } // restore previous levels
		if ($this->listlvl == 0) { $this->listjustfinished = true; }
		return;
	}

	if ($this->listlvl > 1) { // returning one level
		$this->listjustfinished=true;
		if (!empty($this->textbuffer)) {
			$this->listitem[] = array($this->listlvl,$this->listnum,$this->textbuffer,$this->listoccur[$this->listlvl],$this->listitemtype);
		}
		else {
			$this->listnum--;
		}

		$this->textbuffer = array();
		$occur = $this->listoccur[$this->listlvl];
		$this->listlist[$this->listlvl][$occur]['MAXNUM'] = $this->listnum; //save previous lvl's maxnum
		$this->listlvl--;
		$occur = $this->listoccur[$this->listlvl];
		$this->listnum = $this->listlist[$this->listlvl][$occur]['MAXNUM']; // recover previous level's number
		$this->listtype = $this->listlist[$this->listlvl][$occur]['TYPE']; // recover previous level's type
		if ($this->InlineProperties['LIST'][$this->listlvl][$occur]) { $this->restoreInlineProperties($this->InlineProperties['LIST'][$this->listlvl][$occur]); }

	}
	else { // We are closing the last OL/UL tag
		if (!empty($this->textbuffer)) {
			$this->listitem[] = array($this->listlvl,$this->listnum,$this->textbuffer,$this->listoccur[$this->listlvl],$this->listitemtype);
		}
		else {
			$this->listnum--;
		}

		$occur = $this->listoccur[$this->listlvl];
		$this->listlist[$this->listlvl][$occur]['MAXNUM'] = $this->listnum;
		$this->textbuffer = array();
		$this->listlvl--;

		$this->printlistbuffer();
		unset($this->InlineProperties['LIST']);
		// SPACING AFTER LIST (Top level only)
		$this->Ln(0);
		if ($this->list_margin_bottom) {
			$this->DivLn($this->list_margin_bottom,$this->blklvl,true,1); 	// collapsible
		}
		if (isset($this->blk[$this->blklvl]['InlineProperties'])) { $this->restoreInlineProperties($this->blk[$this->blklvl]['InlineProperties']);}
		$this->listjustfinished = true;
		$this->listCSSlvl = 0;
		$this->listcascadeCSS = array();
		$this->blockjustfinished=true;
		$this->lastblockbottommargin = $this->list_margin_bottom;
	}
    }


}


// This function determines the shrink factor when resizing tables
// val is the table_height / page_height_available
// returns a scaling factor used as $shrin_k to resize the table
// Overcompensating will be quicker but may unnecessarily shrink table too much
// Undercompensating means it will reiterate more times (taking more processing time)
function tbsqrt($val, $iteration=3) {
	$k = 4;	// Alters number of iterations until it returns $val itself - Must be > 2
	// Probably best guess and most accurate
	if ($iteration==1) return sqrt($val);
	// Faster than using sqrt (because it won't undercompensate), and gives reasonable results
	//return 1+(($val-1)/2);
	$x = 2-(($iteration-2)/($k-2));
	if ($x == 0) { $ret = $val+0.00001; }
	else if ($x < 0) { $ret = 1 + ( pow(2, ($iteration-2-$k))/1000   ); }
	else { $ret = 1+(($val-1)/$x); }
	return $ret;
}


function printlistbuffer() {
    //Save x coordinate
    $x = $this->lMargin + $this->blk[$this->blklvl]['outer_left_margin'];
    $this->cMarginL = 0;
    $this->cMarginR = 0;
    $currIndentLvl = -1;
    $lastIndent = array();
    $bak_page = $this->page;
    $indent = 0;
    foreach($this->listitem as $item)
    {
	// COLS
	$oldcolumn = $this->CurrCol;

	$this->bulletarray = array();
	//Get list's buffered data
	$this->listlvl = $lvl = $item[0];
	$num = $item[1];
	$this->textbuffer = $item[2];
	$occur = $item[3];
	if ($item[4]) { $type = $item[4]; }	// listitemtype
	else { $type = $this->listlist[$lvl][$occur]['TYPE']; }
	$maxnum = $this->listlist[$lvl][$occur]['MAXNUM'];
	$this->restoreInlineProperties($this->InlineProperties['LIST'][$lvl][$occur]);
	$this->SetFont($this->FontFamily,$this->FontStyle,$this->FontSizePt,true,true);	// force to write
	$clh = $this->FontSize;

	$this->SetLineHeight($this->FontSizePt,$this->list_lineheight[$lvl][$occur]);
	$this->listOcc = $occur;
	$this->listnum = $num;

	if (isset($this->list_align[$this->listlvl][$occur])) { $this->divalign = $this->list_align[$this->listlvl][$occur]; }
	else {
		if (isset($this->blk[$this->blklvl]['direction']) && $this->blk[$this->blklvl]['direction']=='rtl') { $this->divalign = 'R'; }
		else { $this->divalign = 'L'; }
	}

	// Set the bullet fontsize
	$bullfs = $this->InlineProperties['LISTITEM'][$lvl][$occur][$num]['size'];

	$space_width = $this->GetCharWidth(' ',false) * 1.5;

	//Set default width & height values
	$this->divwidth = $this->blk[$this->blklvl]['inner_width'];
	$this->divheight = $this->lineheight;
	$typefont = $this->FontFamily;
	if (preg_match('/U\+([a-fA-F0-9]+)/i',$type,$m)) {
		if ($this->_charDefined($this->CurrentFont['cw'],hexdec($m[1]))) { $list_item_marker = codeHex2utf($m[1]); }
		else { $list_item_marker = '-'; }
		$blt_width = $this->GetStringWidth($list_item_marker);
		$typefont = '';
		if (preg_match('/rgb\(.*?\)/',$type,$m)) {
			$list_item_color = $this->ConvertColor($m[0]);
		}
	}
	else {
		$list_item_color = false;

	  switch($type) //Format type
	  {
          case '1':
		  if ($this->listDir == 'rtl') { $list_item_marker = $this->list_number_suffix . $num; }
		  else { $list_item_marker = $num . $this->list_number_suffix; }
	        $blt_width = $this->GetStringWidth(str_repeat('5',strlen($maxnum)).$this->list_number_suffix);
              break;
          case 'none':
		  $list_item_marker = '';
  	        $blt_width = 0;
             break;
          case 'A':
		  $anum = $this->dec2alpha($num,true);
		  $maxnum = $this->dec2alpha($maxnum,true);
		  if ($this->listDir == 'rtl') { $list_item_marker = $this->list_number_suffix . $anum; }
		  else { $list_item_marker = $anum . $this->list_number_suffix; }
  	        $blt_width = $this->GetStringWidth(str_repeat('W',strlen($maxnum)).$this->list_number_suffix);
             break;
          case 'a':
              $anum = $this->dec2alpha($num,false);
		  $maxnum = $this->dec2alpha($maxnum,false);
		  if ($this->listDir == 'rtl') { $list_item_marker = $this->list_number_suffix . $anum; }
		  else { $list_item_marker = $anum . $this->list_number_suffix; }
		  $blt_width = $this->GetStringWidth(str_repeat('m',strlen($maxnum)).$this->list_number_suffix);
             break;
          case 'I':
              $anum = $this->dec2roman($num,true);
		  if ($this->listDir == 'rtl') { $list_item_marker = $this->list_number_suffix . $anum; }
		  else { $list_item_marker = $anum . $this->list_number_suffix; }
		  
		  if ($maxnum>87) { $bbit = 87; }
		  else if ($maxnum>86) { $bbit = 86; }
		  else if ($maxnum>37) { $bbit = 38; }
		  else if ($maxnum>36) { $bbit = 37; }
		  else if ($maxnum>27) { $bbit = 28; }
		  else if ($maxnum>26) { $bbit = 27; }
		  else if ($maxnum>17) { $bbit = 18; }
		  else if ($maxnum>16) { $bbit = 17; }
		  else if ($maxnum>7) { $bbit = 8; }
		  else if ($maxnum>6) { $bbit = 7; }
		  else if ($maxnum>3) { $bbit = 4; }
		  else { $bbit = $maxnum; }
              $maxlnum = $this->dec2roman($bbit,true);
	        $blt_width = $this->GetStringWidth($maxlnum.$this->list_number_suffix);
              break;
          case 'i':
              $anum = $this->dec2roman($num,false);
		  if ($this->listDir == 'rtl') { $list_item_marker = $this->list_number_suffix . $anum; }
		  else { $list_item_marker = $anum . $this->list_number_suffix; }
		  
		  if ($maxnum>87) { $bbit = 87; }
		  else if ($maxnum>86) { $bbit = 86; }
		  else if ($maxnum>37) { $bbit = 38; }
		  else if ($maxnum>36) { $bbit = 37; }
		  else if ($maxnum>27) { $bbit = 28; }
		  else if ($maxnum>26) { $bbit = 27; }
		  else if ($maxnum>17) { $bbit = 18; }
		  else if ($maxnum>16) { $bbit = 17; }
		  else if ($maxnum>7) { $bbit = 8; }
		  else if ($maxnum>6) { $bbit = 7; }
		  else if ($maxnum>3) { $bbit = 4; }
		  else { $bbit = $maxnum; }
              $maxlnum = $this->dec2roman($bbit,false);
		  
	        $blt_width = $this->GetStringWidth($maxlnum.$this->list_number_suffix);
              break;
          case 'disc':
		  if ($this->PDFA || $this->PDFX) {
			if ($this->_charDefined($this->CurrentFont['cw'],8226)) { $list_item_marker = "\xe2\x80\xa2"; } 	// &#8226;
			else { $list_item_marker = '-'; }
  			$blt_width = $this->GetCharWidth($list_item_marker);
			break;
		  }
              $list_item_marker = chr(108); // bullet disc in Zapfdingbats  'l'
		  $typefont = 'czapfdingbats';
		  $blt_width = (0.791 * $this->FontSize/2.5);
              break;
          case 'circle':
		  if ($this->PDFA || $this->PDFX) {
			if ($this->_charDefined($this->CurrentFont['cw'],9900)) { $list_item_marker = "\xe2\x9a\xac"; } // &#9900;
			else { $list_item_marker = '-'; }
  			$blt_width = $this->GetCharWidth($list_item_marker);
			break;
		  }
              $list_item_marker = chr(109); // circle in Zapfdingbats   'm'
		  $typefont = 'czapfdingbats';
		  $blt_width = (0.873 * $this->FontSize/2.5);
              break;
          case 'square':
		  if ($this->PDFA || $this->PDFX) {
			if ($this->_charDefined($this->CurrentFont['cw'],9642)) { $list_item_marker = "\xe2\x96\xaa"; } // &#9642;
			else { $list_item_marker = '-'; }
  			$blt_width = $this->GetCharWidth($list_item_marker);
			break;
		  }
              $list_item_marker = chr(110); //black square in Zapfdingbats font   'n'
		  $typefont = 'czapfdingbats';
		  $blt_width = (0.761 * $this->FontSize/2.5);
              break;

/*  CSS3 list-styles numeric + I added tamil
arabic-indic | bengali | cambodian | devanagari | gujarati | gurmukhi | kannada | khmer | lao | malayalam | mongolian | myanmar | oriya | persian | telugu | tibetan | thai | urdu
*/
          case 'arabic-indic':
		  $cp = 0x0660;
		  $rnum = $this->dec2other($num, $cp);
		  $list_item_marker = $this->list_number_suffix . $rnum; 	// RTL
  	        $blt_width = $this->GetStringWidth(str_repeat($this->dec2other(3, $cp),strlen($maxnum)).$this->list_number_suffix);
		  break;
          case 'persian':
          case 'urdu':
		  $cp = 0x06F0;
		  $rnum = $this->dec2other($num, $cp);
		  $list_item_marker = $this->list_number_suffix . $rnum; 	// RTL
  	        $blt_width = $this->GetStringWidth(str_repeat($this->dec2other(3, $cp),strlen($maxnum)).$this->list_number_suffix);
		  break;
          case 'bengali':
		  $cp = 0x09E6;
		  $rnum = $this->dec2other($num, $cp);
		  $list_item_marker = $rnum . $this->list_number_suffix;
  	        $blt_width = $this->GetStringWidth(str_repeat($this->dec2other(3, $cp),strlen($maxnum)).$this->list_number_suffix);
		  break;
          case 'devanagari':
		  $cp = 0x0966;
		  $rnum = $this->dec2other($num, $cp);
		  $list_item_marker = $rnum . $this->list_number_suffix;
  	        $blt_width = $this->GetStringWidth(str_repeat($this->dec2other(3, $cp),strlen($maxnum)).$this->list_number_suffix);
		  break;
          case 'gujarati':
		  $cp = 0x0AE6;
		  $rnum = $this->dec2other($num, $cp);
		  $list_item_marker = $rnum . $this->list_number_suffix;
  	        $blt_width = $this->GetStringWidth(str_repeat($this->dec2other(3, $cp),strlen($maxnum)).$this->list_number_suffix);
		  break;
          case 'gurmukhi':
		  $cp = 0x0A66;
		  $rnum = $this->dec2other($num, $cp);
		  $list_item_marker = $rnum . $this->list_number_suffix;
  	        $blt_width = $this->GetStringWidth(str_repeat($this->dec2other(3, $cp),strlen($maxnum)).$this->list_number_suffix);
		  break;
          case 'kannada':
		  $cp = 0x0CE6;
		  $rnum = $this->dec2other($num, $cp);
		  $list_item_marker = $rnum . $this->list_number_suffix;
  	        $blt_width = $this->GetStringWidth(str_repeat($this->dec2other(3, $cp),strlen($maxnum)).$this->list_number_suffix);
		  break;
          case 'malayalam':
		  $cp = 0x0D66;
		  $rnum = $this->dec2other($num, $cp);
		  $list_item_marker = $rnum . $this->list_number_suffix;
  	        $blt_width = $this->GetStringWidth(str_repeat($this->dec2other(6, $cp),strlen($maxnum)).$this->list_number_suffix);
		  break;
          case 'oriya':
		  $cp = 0x0B66;
		  $rnum = $this->dec2other($num, $cp);
		  $list_item_marker = $rnum . $this->list_number_suffix;
  	        $blt_width = $this->GetStringWidth(str_repeat($this->dec2other(3, $cp),strlen($maxnum)).$this->list_number_suffix);
		  break;
          case 'telugu':
		  $cp = 0x0C66;
		  $rnum = $this->dec2other($num, $cp);
		  $list_item_marker = $rnum . $this->list_number_suffix;
  	        $blt_width = $this->GetStringWidth(str_repeat($this->dec2other(3, $cp),strlen($maxnum)).$this->list_number_suffix);
		  break;
          case 'tamil':
		  $cp = 0x0BE6;
		  $rnum = $this->dec2other($num, $cp);
		  $list_item_marker = $rnum . $this->list_number_suffix;
  	        $blt_width = $this->GetStringWidth(str_repeat($this->dec2other(9, $cp),strlen($maxnum)).$this->list_number_suffix);
		  break;
          case 'thai':
		  $cp = 0x0E50;
		  $rnum = $this->dec2other($num, $cp);
		  $list_item_marker = $rnum . $this->list_number_suffix;
  	        $blt_width = $this->GetStringWidth(str_repeat($this->dec2other(5, $cp),strlen($maxnum)).$this->list_number_suffix);
		  break;
	    default:
		  if ($this->listDir == 'rtl') { $list_item_marker = $this->list_number_suffix . $num; }
		  else { $list_item_marker = $num . $this->list_number_suffix; }
	        $blt_width = $this->GetStringWidth(str_repeat('5',strlen($maxnum)).$this->list_number_suffix);
              break;
	  }
	}

	if (isset($item[5]) && $item[5]) { $list_item_marker = ''; }

	if ($currIndentLvl < $lvl) {
		if ($lvl > 1 || $this->list_indent_first_level) {
			$indent += $this->list_indent[$lvl][$occur];
			$lastIndent[$lvl] = $this->list_indent[$lvl][$occur];
		}
	}
	else if ($currIndentLvl > $lvl) {
	    while ($currIndentLvl > $lvl) {
		$indent -= $lastIndent[$currIndentLvl];
		$currIndentLvl--;
	    }
	}
	$currIndentLvl = $lvl;


	  
	  if ($this->list_align_style == 'L') { $lalign = 'L'; }
	  else { $lalign = 'R';  }
        $this->divwidth = $this->blk[$this->blklvl]['width'] - ($indent + $blt_width + $space_width) ;
	  $xb =  $this->blk[$this->blklvl]['padding_left'] + $this->blk[$this->blklvl]['border_left']['w'] - $blt_width - $space_width;
        //Output bullet
	  $this->bulletarray = array('w'=>$blt_width,'h'=>$clh,'txt'=>$list_item_marker,'x'=>$xb,'align'=>$lalign,'font'=>$typefont,'level'=>$lvl, 'occur'=>$occur, 'num'=>$num, 'fontsize'=>$bullfs, 'col'=>$list_item_color );
	  $this->x = $x + $indent + $blt_width + $space_width;

      //Print content
  	$this->printbuffer($this->textbuffer,'',false,true);
      $this->textbuffer=array();

	// Added to correct for OddEven Margins
   	if  ($this->page != $bak_page) {
		if (($this->page-$bak_page) % 2 == 1) {
			$x += $this->MarginCorrection;
		}
		$bak_page = $this->page;
	}

    }

    //Reset all used values
    $this->listoccur = array();
    $this->listitem = array();
    $this->listlist = array();
    $this->listlvl = 0;
    $this->listnum = 0;
    $this->listtype = '';
    $this->textbuffer = array();
    $this->divwidth = 0;
    $this->divheight = 0;
    $this->x = $this->lMargin + $this->blk[$this->blklvl]['outer_left_margin'];
}

function _saveTextBuffer($t, $link = '', $intlink = '') {
	$this->textbuffer[] = array($t,$link,$this->currentfontstyle,$this->colorarray,$this->currentfontfamily,$this->SUP,$this->SUB,$intlink,$this->strike,$this->outlineparam,$this->spanbgcolorarray,$this->currentfontsize,$this->ReqFontStyle,$this->kerning,$this->lSpacingCSS,$this->wSpacingCSS,$this->spanborddet, $this->textshadow);
}

function _saveCellTextBuffer($t, $link = '', $intlink = '') {
	 $this->cell[$this->row][$this->col]['textbuffer'][] = array($t,$link,$this->currentfontstyle,$this->colorarray,$this->currentfontfamily,$this->SUP,$this->SUB,$intlink,$this->strike,$this->outlineparam,$this->spanbgcolorarray,$this->currentfontsize,$this->ReqFontStyle,$this->kerning,$this->lSpacingCSS,$this->wSpacingCSS,$this->spanborddet, $this->textshadow);
}


function printbuffer($arrayaux,$blockstate=0,$is_table=false,$is_list=false)
{
// $blockstate = 0;	// NO margins/padding
// $blockstate = 1;	// Top margins/padding only
// $blockstate = 2;	// Bottom margins/padding only
// $blockstate = 3;	// Top & bottom margins/padding
	$this->spanbgcolorarray = '';
	$this->spanbgcolor = false;
	$this->spanborder = false;
	$this->spanborddet = array();
	$paint_ht_corr = 0;

    	$bak_y = $this->y;
	$bak_x = $this->x;
	$align = '';
	if (!$is_table && !$is_list) {
		if (isset($this->blk[$this->blklvl]['align']) && $this->blk[$this->blklvl]['align']) { $align = $this->blk[$this->blklvl]['align']; }
		// Block-align is set by e.g. <.. align="center"> Takes priority for this block but not inherited
		if (isset($this->blk[$this->blklvl]['block-align']) && $this->blk[$this->blklvl]['block-align']) { $align = $this->blk[$this->blklvl]['block-align']; }
		if (isset($this->blk[$this->blklvl]['direction'])) $blockdir = $this->blk[$this->blklvl]['direction'];
		else $blockdir = "";
		$this->divwidth = $this->blk[$this->blklvl]['width'];
	}
	else {
		$align = $this->divalign;
		if ($is_table) { $blockdir = $this->table[$this->tableLevel][$this->tbctr[$this->tableLevel]]['direction']; }
		else { $blockdir = $this->listDir; }
	}
	$oldpage = $this->page;

	// ADDED for Out of Block now done as Flowing Block
	if ($this->divwidth == 0) {
		$this->divwidth = $this->pgwidth;
	}

	if (!$is_table && !$is_list) { $this->SetLineHeight($this->FontSizePt,$this->blk[$this->blklvl]['line_height']); }
	$this->divheight = $this->lineheight;
	$old_height = $this->divheight;

    // As a failsafe - if font has been set but not output to page
    $this->SetFont($this->default_font,'',$this->default_font_size,true,true);	// force output to page

    $array_size = count($arrayaux);
    $this->newFlowingBlock( $this->divwidth,$this->divheight,$align,$is_table,$is_list,$blockstate,true,$blockdir);

	// Added - Otherwise <div><div><p> did not output top margins/padding for 1st/2nd div
    if ($array_size == 0) { $this->finishFlowingBlock(true); }	// true = END of flowing block

    for($i=0;$i < $array_size; $i++)
    {
	// COLS
	$oldcolumn = $this->CurrCol;

	$vetor = $arrayaux[$i];
	if ($i == 0 and $vetor[0] != "\n" and !$this->ispre) {
		$vetor[0] = ltrim($vetor[0]);
	}

	// FIXED TO ALLOW IT TO SHOW '0'
	if (empty($vetor[0]) && !($vetor[0]==='0') && empty($vetor[7])) { //Ignore empty text and not carrying an internal link
		//Check if it is the last element. If so then finish printing the block
	     	if ($i == ($array_size-1)) { $this->finishFlowingBlock(true); }	// true = END of flowing block
		continue;
	}


	//Activating buffer properties
	if(isset($vetor[11]) and $vetor[11] != '') { 	 // Font Size
		if ($is_table && $this->shrin_k) {
			$this->SetFontSize($vetor[11]/$this->shrin_k,false);
		}
		else {
			$this->SetFontSize($vetor[11],false);
		}
	}

	if(isset($vetor[17]) && !empty($vetor[17])) { //TextShadow
		$this->textshadow = $vetor[17];
	}
	if(isset($vetor[16]) && !empty($vetor[16])) { //Border
		$this->spanborddet = $vetor[16];
		$this->spanborder = true;
	}

	if(isset($vetor[15])) { 	 // Word spacing
		$this->wSpacingCSS = $vetor[15];
		if ($this->wSpacingCSS && strtoupper($this->wSpacingCSS) != 'NORMAL') {
			$this->minwSpacing = $this->ConvertSize($this->wSpacingCSS,$this->FontSize);
		}
	}
	if(isset($vetor[14])) { 	 // Letter spacing
		$this->lSpacingCSS = $vetor[14];
		if (($this->lSpacingCSS || $this->lSpacingCSS==='0') && strtoupper($this->lSpacingCSS) != 'NORMAL') {
			$this->fixedlSpacing = $this->ConvertSize($this->lSpacingCSS,$this->FontSize);
		}
	}
	if(isset($vetor[13])) { 	 // Font Kerning
		$this->kerning = $vetor[13];
	}


	if(isset($vetor[10]) and !empty($vetor[10])) //Background color
	{
		$this->spanbgcolorarray = $vetor[10];
		$this->spanbgcolor = true;
	}
	if(isset($vetor[9]) and !empty($vetor[9])) // Outline parameters
	{
	    $cor = $vetor[9]['COLOR'];
	    $outlinewidth = $vetor[9]['WIDTH'];
	    $this->SetTextOutline($outlinewidth,$cor);
	    $this->outline_on = true;
	}
	if(isset($vetor[8]) and $vetor[8] === true) // strike-through the text
	{
	    $this->strike = true;
	}
	if(isset($vetor[7]) and $vetor[7] != '') // internal link: <a name="anyvalue">
	{
	  $ily = $this->y;
	  if ($this->keep_block_together) { $this->internallink[$vetor[7]] = array("Y"=>$ily,"PAGE"=>$this->page, "kt"=>true ); }
	  else if ($this->table_rotate) { $this->internallink[$vetor[7]] = array("Y"=>$ily,"PAGE"=>$this->page, "tbrot"=>true ); }
	  else if ($this->kwt) { $this->internallink[$vetor[7]] = array("Y"=>$ily,"PAGE"=>$this->page, "kwt"=>true ); }
	  else if ($this->ColActive) { $this->internallink[$vetor[7]] = array("Y"=>$ily,"PAGE"=>$this->page, "col"=>$this->CurrCol ); }
	  else
		$this->internallink[$vetor[7]] = array("Y"=>$ily,"PAGE"=>$this->page );
	  if (empty($vetor[0])) { //Ignore empty text
		//Check if it is the last element. If so then finish printing the block
		if ($i == ($array_size-1)) { $this->finishFlowingBlock(true); }	// true = END of flowing block
		continue;
	  }
	}
	if(isset($vetor[6]) and $vetor[6] === true) // Subscript
	{
		$this->SUB = true;
	}
	if(isset($vetor[5]) and $vetor[5] === true) // Superscript
	{
		$this->SUP = true;
	}
	if(isset($vetor[4]) and $vetor[4] != '') {  // Font Family
		$font = $this->SetFont($vetor[4],$this->FontStyle,0,false);
	}
	if (!empty($vetor[3])) //Font Color
	{
		$cor = $vetor[3];
		$this->SetTColor($cor);
	}
	if(isset($vetor[2]) and $vetor[2] != '') //Bold,Italic,Underline styles
	{
		$this->SetStyles($vetor[2]);
	}

	if(isset($vetor[12]) and $vetor[12] != '') { //Requested Bold,Italic,Underline
		$this->ReqFontStyle = $vetor[12];
	}
	if(isset($vetor[1]) and $vetor[1] != '') //LINK
	{
	  
	  if (strpos($vetor[1],".") === false && strpos($vetor[1],"@") !== 0) //assuming every external link has a dot indicating extension (e.g: .html .txt .zip www.somewhere.com etc.)
	  {
	    //Repeated reference to same anchor?
	    while(array_key_exists($vetor[1],$this->internallink)) $vetor[1]="#".$vetor[1];
	    $this->internallink[$vetor[1]] = $this->AddLink();
	    $vetor[1] = $this->internallink[$vetor[1]];
	  }
	  $this->HREF = $vetor[1];					// HREF link style set here ******
	}

	// SPECIAL CONTENT - IMAGES & FORM OBJECTS
	//Print-out special content

	if (substr($vetor[0],0,3) == "\xbb\xa4\xac") { //identifier has been identified!
	  
	  $objattr = $this->_getObjAttr($vetor[0]);

	  if ($objattr['type'] == 'nestedtable') {
		if ($objattr['nestedcontent']) {
			$level = $objattr['level'];
			$table = &$this->table[$level][$objattr['table']];
			if ($this->cacheTables) {
				$fh = fopen($table['cache'], "r+b");
				$cell = $this->_uncacheCell($table['cells'][$objattr['row']][$objattr['col']], '', $fh);
			}
			else {
				$fh = null;
				$cell = &$table['cells'][$objattr['row']][$objattr['col']];
			}
			$this->finishFlowingBlock(false,'nestedtable');
			$save_dw = $this->divwidth ;
			$save_buffer = $this->cellBorderBuffer;
			$this->cellBorderBuffer = array();
			$ncx = $this->x;
			list($dummyx,$w) = $this->_tableGetWidth($table, $objattr['row'], $objattr['col'], $fh);
			$ntw = $this->table[($level+1)][$objattr['nestedcontent']]['w'];	// nested table width
			if (!$this->simpleTables){
				if ($this->packTableData) {
			 	   	list($bt,$br,$bb,$bl) = $this->_getBorderWidths($cell['borderbin']);
				}
				else {
					$br = $cell['border_details']['R']['w'];
					$bl = $cell['border_details']['L']['w'];
				}
				if ($table['borders_separate']) {
					$innerw = $w - $bl - $br - $cell['padding']['L'] - $cell['padding']['R'] - $table['border_spacing_H'];
				}
				else {
					$innerw = $w - $bl/2 - $br/2 - $cell['padding']['L'] - $cell['padding']['R'];
				}
			}
			else if ($this->simpleTables){
				if ($table['borders_separate']) {
					$innerw = $w - $table['simple']['border_details']['L']['w'] - $table['simple']['border_details']['R']['w'] - $cell['padding']['L'] - $cell['padding']['R'] - $table['border_spacing_H'];
				}
				else {
					$innerw = $w - $table['simple']['border_details']['L']['w']/2 - $table['simple']['border_details']['R']['w']/2 - $cell['padding']['L'] - $cell['padding']['R'];
				}
			}
			if ($cell['a']=='C' || $this->table[($level+1)][$objattr['nestedcontent']]['a']=='C') {
				$ncx += ($innerw-$ntw)/2;
			}
			elseif ($cell['a']=='R' || $this->table[($level+1)][$objattr['nestedcontent']]['a']=='R') {
				$ncx += $innerw- $ntw;
			}
			$this->x = $ncx ;
			if ($this->cacheTables) { fclose($fh); }

			$this->_tableWrite($this->table[($level+1)][$objattr['nestedcontent']]);
			$this->cellBorderBuffer = $save_buffer;
			$this->x = $bak_x ;
			$this->divwidth  = $save_dw;
			$this->newFlowingBlock( $this->divwidth,$this->divheight,$align,$is_table,$is_list,$blockstate,false,$blockdir);
		}
	  }
	  else {
		if ($is_table) {	// *TABLES*
			$maxWidth = $this->divwidth; 	// *TABLES*
		}	// *TABLES*
		else {	// *TABLES*
			$maxWidth = $this->divwidth - ($this->blk[$this->blklvl]['padding_left'] + $this->blk[$this->blklvl]['border_left']['w'] + $this->blk[$this->blklvl]['padding_right'] + $this->blk[$this->blklvl]['border_right']['w']);
		}	// *TABLES*


		list($skipln) = $this->inlineObject($objattr['type'], '', $this->y, $objattr,$this->lMargin, ($this->flowingBlockAttr['contentWidth']/_MPDFK), $maxWidth, $this->flowingBlockAttr['height'], false, $is_table);
		//  1 -> New line needed because of width
		// -1 -> Will fit width on line but NEW PAGE REQUIRED because of height
		// -2 -> Will not fit on line therefore needs new line but thus NEW PAGE REQUIRED
		$iby = $this->y;
		$oldpage = $this->page;
		$oldcol = $this->CurrCol;
		if (($skipln == 1 || $skipln == -2) && !isset($objattr['float'])) {
			$this->finishFlowingBlock(false,$objattr['type']);
		     	$this->newFlowingBlock( $this->divwidth,$this->divheight,$align,$is_table,$is_list,$blockstate,false,$blockdir);
		}
		$thispage = $this->page;
		if ($this->CurrCol!=$oldcol) { $changedcol = true; }
		else { $changedcol=false; }

		// the previous lines can already have triggered page break or column change
		if (!$changedcol && $skipln <0 && $this->AcceptPageBreak() && $thispage==$oldpage) {

			$this->AddPage($this->CurOrientation);

	  		// Added to correct Images already set on line before page advanced
			// i.e. if second inline image on line is higher than first and forces new page
			if (count($this->objectbuffer)) {
				$yadj = $iby - $this->y;
				foreach($this->objectbuffer AS $ib=>$val) {
					if ($this->objectbuffer[$ib]['OUTER-Y'] ) $this->objectbuffer[$ib]['OUTER-Y'] -= $yadj;
					if ($this->objectbuffer[$ib]['BORDER-Y']) $this->objectbuffer[$ib]['BORDER-Y'] -= $yadj;
					if ($this->objectbuffer[$ib]['INNER-Y']) $this->objectbuffer[$ib]['INNER-Y'] -= $yadj;
				}
			}
		}

	  	// Added to correct for OddEven Margins
   	  	if  ($this->page != $oldpage) {
			if (($this->page-$oldpage) % 2 == 1) {
				$bak_x += $this->MarginCorrection;
			}
			$oldpage = $this->page;
				$y = $this->tMargin - $paint_ht_corr ;
				$this->oldy = $this->tMargin - $paint_ht_corr ;
				$old_height = 0;
		}
		$this->x = $bak_x;

			$this->WriteFlowingBlock($vetor[0]);
	  }	// *TABLES*

	}	// END If special content
      else { //THE text
	  if ($this->tableLevel) { $paint_ht_corr = 0; }	// To move the y up when new column/page started if div border needed
	  else { $paint_ht_corr = $this->blk[$this->blklvl]['border_top']['w']; }

        if ($vetor[0] == "\n") { //We are reading a <BR> now turned into newline ("\n")
		if ($this->flowingBlockAttr['content']) {
			$this->finishFlowingBlock(false,'br');
		}
		else if ($is_table) {
			$this->y+= $this->_computeLineheight($this->table_lineheight);
		}
		else if (!$is_table) {
			$this->DivLn($this->lineheight);
		}
	  	// Added to correct for OddEven Margins
   	  	if  ($this->page != $oldpage) {
			if (($this->page-$oldpage) % 2 == 1) {
				$bak_x += $this->MarginCorrection;
			}
			$oldpage = $this->page;
				$y = $this->tMargin - $paint_ht_corr ;
				$this->oldy = $this->tMargin - $paint_ht_corr ;
				$old_height = 0;
		}
		$this->x = $bak_x;
		$this->newFlowingBlock( $this->divwidth,$this->divheight,$align,$is_table,$is_list,$blockstate,false,$blockdir);
         }
         else {
		$this->WriteFlowingBlock( $vetor[0]);

		  // Added to correct for OddEven Margins
   		  if  ($this->page != $oldpage) {
			if (($this->page-$oldpage) % 2 == 1) {
				$bak_x += $this->MarginCorrection;
				$this->x = $bak_x;
			}
			$oldpage = $this->page;
				$y = $this->tMargin - $paint_ht_corr ;
				$this->oldy = $this->tMargin - $paint_ht_corr ;
				$old_height = 0;
		  }
	    }


      }

      //Check if it is the last element. If so then finish printing the block
      if ($i == ($array_size-1)) {
		$this->finishFlowingBlock(true);	// true = END of flowing block
		  // Added to correct for OddEven Margins
   		  if  ($this->page != $oldpage) {
			if (($this->page-$oldpage) % 2 == 1) {
				$bak_x += $this->MarginCorrection;
				$this->x = $bak_x;
			}
			$oldpage = $this->page;
				$y = $this->tMargin - $paint_ht_corr ;
				$this->oldy = $this->tMargin - $paint_ht_corr ;
				$old_height = 0;
		  }


	}

	// RESETTING VALUES
	$this->SetTColor($this->ConvertColor(0));
	$this->SetDColor($this->ConvertColor(0));
	$this->SetFColor($this->ConvertColor(255));
	$this->colorarray = '';
	$this->spanbgcolorarray = '';
	$this->spanbgcolor = false;
	$this->spanborder = false;
	$this->spanborddet = array();
	$this->HREF = '';
	$this->outlineparam = array();
	$this->SetTextOutline(false);
	$this->outline_on = false;
	$this->SUP = false;
	$this->SUB = false;

	$this->strike = false;
	$this->textshadow = '';

	$this->currentfontfamily = '';
	$this->currentfontsize = '';
	$this->currentfontstyle = '';
	if ($this->tableLevel) {
		$this->SetLineHeight('',$this->table_lineheight);	// *TABLES*
	}
	else
	if ($is_list && $this->list_lineheight[$this->listlvl][$this->listOcc]) {
		$this->SetLineHeight('',$this->list_lineheight[$this->listlvl][$this->listOcc]);	// sets default line height
	}
	else
	if (isset($this->blk[$this->blklvl]['line_height']) && $this->blk[$this->blklvl]['line_height']) {
		$this->SetLineHeight('',$this->blk[$this->blklvl]['line_height']);	// sets default line height
	}
	$this->ResetStyles();
	$this->toupper = false;
	$this->tolower = false;
	$this->capitalize = false;
	$this->kerning = false;
	$this->lSpacingCSS = '';
	$this->wSpacingCSS = '';
	$this->fixedlSpacing = false;
	$this->minwSpacing = 0;
	$this->SetDash();
	$this->dash_on = false;
	$this->dotted_on = false;

    }//end of for(i=0;i<arraysize;i++)


    // PAINT DIV BORDER	// DISABLED IN COLUMNS AS DOESN'T WORK WHEN BROKEN ACROSS COLS??
    if ((isset($this->blk[$this->blklvl]['border']) || isset($this->blk[$this->blklvl]['bgcolor']) || isset($this->blk[$this->blklvl]['box_shadow'])) && $blockstate  && ($this->y != $this->oldy)) {
	$bottom_y = $this->y;	// Does not include Bottom Margin
	if (isset($this->blk[$this->blklvl]['startpage']) && $this->blk[$this->blklvl]['startpage'] != $this->page && $blockstate != 1) {
		$this->PaintDivBB('pagetop',$blockstate);
	}

	else if ($blockstate != 1) {
		$this->PaintDivBB('',$blockstate);
	}
	$this->y = $bottom_y;
	$this->x = $bak_x;
    }

    // Reset Font
    $this->SetFontSize($this->default_font_size,false);


}

function _setDashBorder($style, $div, $cp, $side) {
	if ($style == 'dashed' && (($side=='L' || $side=='R') || ($side=='T' && $div != 'pagetop' && !$cp) || ($side=='B' && $div!='pagebottom') )) {
		$dashsize = 2;	// final dash will be this + 1*linewidth
		$dashsizek = 1.5;	// ratio of Dash/Blank
		$this->SetDash($dashsize,($dashsize/$dashsizek)+($this->LineWidth*2));
	}
	else if ($style == 'dotted' || ($side=='T' && ($div == 'pagetop' || $cp)) || ($side=='B' && $div == 'pagebottom')) {
  		//Round join and cap
		$this->SetLineJoin(1);
		$this->SetLineCap(1);
		$this->SetDash(0.001,($this->LineWidth*3));
	}
}

function _setBorderLine($b, $k=1) {
	$this->SetLineWidth($b['w']/$k);
	$this->SetDColor($b['c']);
	if ($b['c'][0]==5) {	// RGBa
		$this->SetAlpha($b['c'][4], 'Normal', false, 'S')."\n";
	}
	else if ($b['c'][0]==6) {	// CMYKa
		$this->SetAlpha($b['c'][5], 'Normal', false, 'S')."\n";
	}
}

function PaintDivBB($divider='',$blockstate=0,$blvl=0) {
	// Borders & backgrounds are done elsewhere for columns - messes up the repositioning in printcolumnbuffer
	$save_y = $this->y;
	if (!$blvl) { $blvl = $this->blklvl; }
	$x0 = $x1 = $y0 = $y1 = 0;

	// Added mPDF 3.0 Float DIV

	if (isset($this->blk[$blvl]['x0'])) { $x0 = $this->blk[$blvl]['x0']; }	// left
	if (isset($this->blk[$blvl]['y1'])) { $y1 = $this->blk[$blvl]['y1']; }	// bottom

	// Added mPDF 3.0 Float DIV - ensures backgrounds/borders are drawn to bottom of page
	if ($y1==0) {
		if ($divider=='pagebottom') { $y1 = $this->h-$this->bMargin; }
		else { $y1 = $this->y; }
	}

	if (isset($this->blk[$blvl]['startpage']) && $this->blk[$blvl]['startpage'] != $this->page) { $continuingpage = true; } else { $continuingpage = false; }

	if (isset($this->blk[$blvl]['y0'])) { $y0 = $this->blk[$blvl]['y0']; }
	$h = $y1 - $y0;
	$w = $this->blk[$blvl]['width'];
	$x1 = $x0 + $w;

	// Set border-widths as used here
	$border_top = $this->blk[$blvl]['border_top']['w'];
	$border_bottom = $this->blk[$blvl]['border_bottom']['w'];
	$border_left = $this->blk[$blvl]['border_left']['w'];
	$border_right = $this->blk[$blvl]['border_right']['w'];
	if (!$this->blk[$blvl]['border_top'] || $divider == 'pagetop' || $continuingpage) {
		$border_top = 0;
	}
	if (!$this->blk[$blvl]['border_bottom'] || $blockstate == 1 || $divider == 'pagebottom') {
		$border_bottom = 0;
	}

		$brTL_H = 0;
		$brTL_V = 0;
		$brTR_H = 0;
		$brTR_V = 0;
		$brBL_H = 0;
		$brBL_V = 0;
		$brBR_H = 0;
		$brBR_V = 0;

	$brset = false;

	// BORDERS
	if (isset($this->blk[$blvl]['y0']) && $this->blk[$blvl]['y0']) { $y0 = $this->blk[$blvl]['y0']; }
	$h = $y1 - $y0;
	$w = $this->blk[$blvl]['width'];

	//if ($this->blk[$blvl]['border_top']) {
	// Reinstate line above for dotted line divider when block border crosses a page
	if ($this->blk[$blvl]['border_top'] && $divider != 'pagetop' && !$continuingpage) {
		$tbd = $this->blk[$blvl]['border_top'];

		// mPDF 5.4.18
		$legend = '';
		if (isset($this->blk[$blvl]['border_legend']) && $this->blk[$blvl]['border_legend']) {
			$legend = $this->blk[$blvl]['border_legend'];	// Same structure array as textbuffer
			$txt = ltrim($legend[0]);

			//Set font, size, style, color
			$this->SetFont($legend[4],$legend[2],$legend[11]);
			if ($legend[3]) {
				$cor = $legend[3];
				$this->SetTColor($cor);
			}
			$stringWidth = $this->GetStringWidth($txt );
			$save_x = $this->x;
			$save_y = $this->y;
			$save_currentfontfamily = $this->FontFamily;
			$save_currentfontsize = $this->FontSizePt;
			$save_currentfontstyle = $this->FontStyle.($this->U ? 'U' : '').($this->S ? 'S' : '');
			$this->y = $y0 - $this->FontSize/2  + $this->blk[$blvl]['border_top']['w']/2;
			$this->x = $x0 + $this->blk[$blvl]['padding_left'] + $this->blk[$blvl]['border_left']['w'];

			// Set the distance from the border line to the text ? make configurable variable
			$gap = 0.2 * $this->FontSize;

			$legbreakL = $this->x - $gap;
			$legbreakR = $this->x + $stringWidth + $gap;

			$this->Cell( $stringWidth, $this->FontSize, $txt , '', 0, 'C', $fill, '', 0, 0,0,'M', $fill);
			// Reset
			$this->x = $save_x;
			$this->y = $save_y;
			$this->SetFont($save_currentfontfamily,$save_currentfontstyle,$save_currentfontsize);
			$this->SetTColor($this->ConvertColor(0));
		}

		if (isset($tbd['s']) && $tbd['s']) {
			$this->_setBorderLine($tbd);
			if ($tbd['style']=='dotted' || $tbd['style']=='dashed') { $this->_setDashBorder($tbd['style'],$divider,$continuingpage,'T'); }
				$this->_out(sprintf('%.3F %.3F m ',($x0 + $w - ($border_top/2))*_MPDFK, ($this->h-($y0 + ($border_top/2)))*_MPDFK));
				// mPDF 5.4.18
				if ($legend) {
					$legbreakL -= $border_top/2;	// because line cap different if not radius
					$legbreakR += $border_top/2;
					if ($legbreakR < ($x0 + $w - ($border_top/2))) {
						$this->_out(sprintf('%.3F %.3F l ',$legbreakR*_MPDFK, ($this->h-($y0 + ($border_top/2)))*_MPDFK));
					}
					if ($legbreakL > ($x0 + $border_top/2)) {
						$this->_out(sprintf('%.3F %.3F m ',$legbreakL*_MPDFK, ($this->h-($y0 + ($border_top/2)))*_MPDFK));
						$this->_out(sprintf('%.3F %.3F l ',($x0 + ($border_top/2))*_MPDFK, ($this->h-($y0 + ($border_top/2)))*_MPDFK));
					}
					else {
						$this->_out(sprintf('%.3F %.3F m ', ($x0 + $border_top/2)*_MPDFK, ($this->h-($y0 + ($border_top/2)))*_MPDFK));
					}
				}
				else {
					$this->_out(sprintf('%.3F %.3F l ',($x0 + ($border_top/2))*_MPDFK, ($this->h-($y0 + ($border_top/2)))*_MPDFK));
				}
			$this->_out('S');

			// Reset Corners and Dash off
			$this->SetLineJoin(2);
			$this->SetLineCap(2);
			$this->SetDash();
		}
	}
	//if ($this->blk[$blvl]['border_bottom'] && $blockstate != 1) {
	// Reinstate line above for dotted line divider when block border crosses a page
	if ($this->blk[$blvl]['border_bottom'] && $blockstate != 1 && $divider != 'pagebottom') {
		$tbd = $this->blk[$blvl]['border_bottom'];
		if (isset($tbd['s']) && $tbd['s']) {
			$this->_setBorderLine($tbd);
			if ($tbd['style']=='dotted' || $tbd['style']=='dashed') { $this->_setDashBorder($tbd['style'],$divider,$continuingpage,'B'); }
				$this->_out(sprintf('%.3F %.3F m ',($x0 + ($border_bottom/2))*_MPDFK, ($this->h-($y0 + $h - ($border_bottom/2)))*_MPDFK));
				$this->_out(sprintf('%.3F %.3F l ',($x0 + $w - ($border_bottom/2) )*_MPDFK, ($this->h-($y0 + $h - ($border_bottom/2)))*_MPDFK));
			$this->_out('S');

			// Reset Corners and Dash off
			$this->SetLineJoin(2);
			$this->SetLineCap(2);
			$this->SetDash();
		}
	}
	if ($this->blk[$blvl]['border_left']) {
		$tbd = $this->blk[$blvl]['border_left'];
		if (isset($tbd['s']) && $tbd['s']) {
			$this->_setBorderLine($tbd);
			if ($tbd['style']=='dotted' || $tbd['style']=='dashed') { $this->_setDashBorder($tbd['style'],$divider,$continuingpage,'L'); }
				$this->_out(sprintf('%.3F %.3F m ',($x0 + ($border_left/2))*_MPDFK, ($this->h-($y0 + ($border_left/2)))*_MPDFK));
				$this->_out(sprintf('%.3F %.3F l ',($x0 + ($border_left/2))*_MPDFK, ($this->h-($y0 + $h - ($border_left/2)) )*_MPDFK));
			$this->_out('S');

			// Reset Corners and Dash off
			$this->SetLineJoin(2);
			$this->SetLineCap(2);
			$this->SetDash();
		}
	}
	if ($this->blk[$blvl]['border_right']) {
		$tbd = $this->blk[$blvl]['border_right'];
		if (isset($tbd['s']) && $tbd['s']) {
			$this->_setBorderLine($tbd);
			if ($tbd['style']=='dotted' || $tbd['style']=='dashed') { $this->_setDashBorder($tbd['style'],$divider,$continuingpage,'R'); }
				$this->_out(sprintf('%.3F %.3F m ',($x0 + $w - ($border_right/2))*_MPDFK, ($this->h-($y0 + $h - ($border_right/2)))*_MPDFK));
				$this->_out(sprintf('%.3F %.3F l ',($x0 + $w - ($border_right/2))*_MPDFK, ($this->h-($y0 + ($border_right/2)) )*_MPDFK));
			$this->_out('S');

			// Reset Corners and Dash off
			$this->SetLineJoin(2);
			$this->SetLineCap(2);
			$this->SetDash();
		}
	}

	if (!$brset) {	// not if border-radius used
		$tbcol = $this->ConvertColor(255);
		for($l=0; $l <= $blvl; $l++) {
			if ($this->blk[$l]['bgcolor']) {
				$tbcol = $this->blk[$l]['bgcolorarray'];
			}
		}
	}
	if ($this->blk[$blvl]['border_top'] && $divider != 'pagetop' && !$continuingpage) {
		$tbd = $this->blk[$blvl]['border_top'];
		if (isset($tbd['s']) && $tbd['s']) {
			if (!$brset && $tbd['style']=='double') {
				$this->SetLineWidth($tbd['w']/3);
				$this->SetDColor($tbcol);
				$this->_out(sprintf('%.3F %.3F m %.3F %.3F l S', ($x0 + $w - ($border_top/2))*_MPDFK, ($this->h-($y0 + ($border_top/2)))*_MPDFK, ($x0 + ($border_top/2))*_MPDFK, ($this->h-($y0 + ($border_top/2)))*_MPDFK ));
			}
		}
	}
	if ($this->blk[$blvl]['border_bottom'] && $blockstate != 1 && $divider != 'pagebottom') {
		$tbd = $this->blk[$blvl]['border_bottom'];
		if (isset($tbd['s']) && $tbd['s']) {
			if (!$brset && $tbd['style']=='double') {
				$this->SetLineWidth($tbd['w']/3);
				$this->SetDColor($tbcol);
				$this->_out(sprintf('%.3F %.3F m %.3F %.3F l S', ($x0 + ($border_bottom/2))*_MPDFK, ($this->h-($y0 + $h - ($border_bottom/2)))*_MPDFK, ($x0 + $w - ($border_bottom/2) )*_MPDFK, ($this->h-($y0 + $h - ($border_bottom/2)))*_MPDFK ));
			}
		}
	}
	if ($this->blk[$blvl]['border_left']) {
		$tbd = $this->blk[$blvl]['border_left'];
		if (isset($tbd['s']) && $tbd['s']) {
			if (!$brset && $tbd['style']=='double') {
				$this->SetLineWidth($tbd['w']/3);
				$this->SetDColor($tbcol);
				$this->_out(sprintf('%.3F %.3F m %.3F %.3F l S', ($x0 + ($border_left/2))*_MPDFK, ($this->h-($y0 + ($border_left/2)))*_MPDFK, ($x0 + ($border_left/2))*_MPDFK, ($this->h-($y0 + $h - ($border_left/2)) )*_MPDFK));
			}
		}
	}
	if ($this->blk[$blvl]['border_right']) {
		$tbd = $this->blk[$blvl]['border_right'];
		if (isset($tbd['s']) && $tbd['s']) {
			if (!$brset && $tbd['style']=='double') {
				$this->SetLineWidth($tbd['w']/3);
				$this->SetDColor($tbcol);
				$this->_out(sprintf('%.3F %.3F m %.3F %.3F l S', ($x0 + $w - ($border_right/2))*_MPDFK, ($this->h-($y0 + $h - ($border_right/2)))*_MPDFK, ($x0 + $w - ($border_right/2))*_MPDFK, ($this->h-($y0 + ($border_right/2)) )*_MPDFK));
			}
		}
	}

	$this->SetDash();
	$this->y = $save_y;


	// BACKGROUNDS are disabled in columns/kbt/headers - messes up the repositioning in printcolumnbuffer
	if ($this->ColActive || $this->kwt || $this->keep_block_together) { return ; }


	$bgx0 = $x0;
	$bgx1 = $x1;
	$bgy0 = $y0;
	$bgy1 = $y1;

		$brbgTL_H = $brTL_H;
		$brbgTL_V = $brTL_V;
		$brbgTR_H = $brTR_H;
		$brbgTR_V = $brTR_V;
		$brbgBL_H = $brBL_H;
		$brbgBL_V = $brBL_V;
		$brbgBR_H = $brBR_H;
		$brbgBR_V = $brBR_V;

	// Set clipping path
	$s = ' q 0 w ';	// Line width=0
	$s .= sprintf('%.3F %.3F m ', ($bgx0+$brbgTL_H )*_MPDFK, ($this->h-$bgy0)*_MPDFK);	// start point TL before the arc
	$s .= sprintf('%.3F %.3F l ', ($bgx0)*_MPDFK, ($this->h-($bgy1-$brbgBL_V ))*_MPDFK);	// line to BL
	$s .= sprintf('%.3F %.3F l ', ($bgx1-$brbgBR_H )*_MPDFK, ($this->h-($bgy1))*_MPDFK);	// line to BR
	$s .= sprintf('%.3F %.3F l ', ($bgx1)*_MPDFK, ($this->h-($bgy0+$brbgTR_V))*_MPDFK);	// line to TR
	$s .= sprintf('%.3F %.3F l ', ($bgx0+$brbgTL_H )*_MPDFK, ($this->h-$bgy0)*_MPDFK);	// line to TL


	// Box Shadow
	$shadow = '';
	if (isset($this->blk[$blvl]['box_shadow']) && $this->blk[$blvl]['box_shadow'] && $h > 0) {
		foreach($this->blk[$blvl]['box_shadow'] AS $sh) {
			// Colors
			if ($sh['col']{0}==1) {
				$colspace = 'Gray';
				if ($sh['col']{2}==1) { $col1 = '1'.$sh['col'][1].'1'.$sh['col'][3]; }
				else { $col1 = '1'.$sh['col'][1].'1'.chr(100); }
				$col2 = '1'.$sh['col'][1].'1'.chr(0);
			}
			else if ($sh['col']{0}==4) {	// CMYK
				$colspace = 'CMYK';
				$col1 = '6'.$sh['col'][1].$sh['col'][2].$sh['col'][3].$sh['col'][4].chr(100);
				$col2 = '6'.$sh['col'][1].$sh['col'][2].$sh['col'][3].$sh['col'][4].chr(0);
			}
			else if ($sh['col']{0}==5) {	// RGBa
				$colspace = 'RGB';
				$col1 = '5'.$sh['col'][1].$sh['col'][2].$sh['col'][3].$sh['col'][4];
				$col2 = '5'.$sh['col'][1].$sh['col'][2].$sh['col'][3].chr(0);
			}
			else if ($sh['col']{0}==6) {	// CMYKa
				$colspace = 'CMYK';
				$col1 = '6'.$sh['col'][1].$sh['col'][2].$sh['col'][3].$sh['col'][4].$sh['col'][5];
				$col2 = '6'.$sh['col'][1].$sh['col'][2].$sh['col'][3].$sh['col'][4].chr(0);
			}
			else {
				$colspace = 'RGB';
				$col1 = '5'.$sh['col'][1].$sh['col'][2].$sh['col'][3].chr(100);
				$col2 = '5'.$sh['col'][1].$sh['col'][2].$sh['col'][3].chr(0);
			}

			// Use clipping path as set above (and rectangle around page) to clip area outside box
			$shadow .= $s;	// Use the clipping path with W*
			$shadow .= sprintf('0 %.3F m %.3F %.3F l ', $this->h*_MPDFK, $this->w*_MPDFK, $this->h*_MPDFK);
			$shadow .= sprintf('%.3F 0 l 0 0 l 0 %.3F l ', $this->w*_MPDFK, $this->h*_MPDFK);
			$shadow .= 'W n'."\n";

			$sh['blur'] = abs($sh['blur']);	// cannot have negative blur value
			// Ensure spread/blur do not make effective shadow width/height < 0
			// Could do more complex things but this just adjusts spread value
			if (-$sh['spread'] + $sh['blur']/2 > min($w/2, $h/2)) {
				$sh['spread'] = $sh['blur']/2 - min($w/2, $h/2) + 0.01;
			}
			// Shadow Offset
			if ($sh['x'] || $sh['y']) $shadow .= sprintf(' q 1 0 0 1 %.4F %.4F cm', $sh['x']*_MPDFK, -$sh['y']*_MPDFK)."\n";
 
			// Set path for INNER shadow
			$shadow .= ' q 0 w ';
			$shadow .= $this->SetFColor($col1, true)."\n";
			if ($col1{0}==5 && ord($col1{4})<100) {	// RGBa
				$shadow .= $this->SetAlpha(ord($col1{4})/100, 'Normal', true, 'F')."\n";
			}
			else if ($col1{0}==6 && ord($col1{5})<100) {	// CMYKa
				$shadow .= $this->SetAlpha(ord($col1{5})/100, 'Normal', true, 'F')."\n";
			}
			else if ($col1{0}==1 && $col1{2}==1 && ord($col1{3})<100) {	// Gray
				$shadow .= $this->SetAlpha(ord($col1{3})/100, 'Normal', true, 'F')."\n";
			}

			// Blur edges
			$mag = 0.551784;	// Bezier Control magic number for 4-part spline for circle/ellipse
			$mag2 = 0.551784;	// Bezier Control magic number to fill in edge of blurred rectangle
			$d1 = $sh['spread']+$sh['blur']/2;
			$d2 = $sh['spread']-$sh['blur']/2;
			$bl = $sh['blur'];
			$x00 = $x0 - $d1;
			$y00 = $y0 - $d1;
			$w00 = $w + $d1*2;
			$h00 = $h + $d1*2;

			// If any border-radius is greater width-negative spread(inner edge), ignore radii for shadow or screws up
			$flatten = false;
			if (max($brbgTR_H, $brbgTL_H, $brbgBR_H, $brbgBL_H) >= $w+$d2) { $flatten = true; }
			if (max($brbgTR_V, $brbgTL_V, $brbgBR_V, $brbgBL_V) >= $h+$d2) { $flatten = true; }


			// TOP RIGHT corner
			$p1x = $x00+$w00-$d1-$brbgTR_H;	$p1c2x = $p1x +($d2+$brbgTR_H)*$mag;
			$p1y = $y00+$bl;
			$p2x = $x00+$w00-$d1-$brbgTR_H;	$p2c2x = $p2x + ($d1+$brbgTR_H)*$mag;
			$p2y = $y00;				$p2c1y = $p2y + $bl/2;
			$p3x = $x00+$w00;				$p3c2x = $p3x - $bl/2;
			$p3y = $y00+$d1+$brbgTR_V;		$p3c1y = $p3y - ($d1+$brbgTR_V)*$mag;
			$p4x = $x00+$w00-$bl;
			$p4y = $y00+$d1+$brbgTR_V;		$p4c2y = $p4y - ($d2+$brbgTR_V)*$mag;
			if (-$d2 > min($brbgTR_H, $brbgTR_V) || $flatten) {
				$p1x = $x00+$w00-$bl;	$p1c2x = $p1x;
				$p2x = $x00+$w00-$bl;	$p2c2x = $p2x + $bl*$mag2;
				$p3y = $y00+$bl;		$p3c1y = $p3y - $bl*$mag2;
				$p4y = $y00+$bl;		$p4c2y = $p4y ;
			}

			$shadow .= sprintf('%.3F %.3F m ', ($p1x )*_MPDFK, ($this->h-($p1y ))*_MPDFK);
			$shadow .= sprintf('%.3F %.3F %.3F %.3F %.3F %.3F c ', ($p1c2x)*_MPDFK, ($this->h-($p1y))*_MPDFK, ($p4x)*_MPDFK, ($this->h-($p4c2y))*_MPDFK, ($p4x)*_MPDFK, ($this->h-($p4y))*_MPDFK);
			$patch_array[0]['f']=0;
			$patch_array[0]['points']=array($p1x,$p1y, $p1x,$p1y,
				$p2x,$p2c1y, $p2x,$p2y, $p2c2x,$p2y,
				$p3x,$p3c1y, $p3x,$p3y, $p3c2x,$p3y,
				$p4x,$p4y, $p4x,$p4y, $p4x,$p4c2y,
				$p1c2x,$p1y);
			$patch_array[0]['colors'] = array($col1,$col2,$col2,$col1);


			// RIGHT
			$p1x = $x00+$w00;	// control point only matches p3 preceding
			$p1y = $y00+$d1+$brbgTR_V;
			$p2x = $x00+$w00-$bl;	// control point only matches p4 preceding
			$p2y = $y00+$d1+$brbgTR_V;
			$p3x = $x00+$w00-$bl;
			$p3y = $y00+$h00-$d1-$brbgBR_V;
			$p4x = $x00+$w00;		$p4c1x = $p4x-$bl/2;
			$p4y = $y00+$h00-$d1-$brbgBR_V;
			if (-$d2 > min($brbgTR_H, $brbgTR_V) || $flatten) {
				$p1y = $y00+$bl;
				$p2y = $y00+$bl;
			}
			if (-$d2 > min($brbgBR_H, $brbgBR_V) || $flatten) {
				$p3y = $y00+$h00-$bl;
				$p4y = $y00+$h00-$bl;
			}

			$shadow .= sprintf('%.3F %.3F l ', ($p3x )*_MPDFK, ($this->h-($p3y ))*_MPDFK);
			$patch_array[1]['f']=2;
			$patch_array[1]['points']=array($p2x,$p2y,
				$p3x,$p3y, $p3x,$p3y, $p3x,$p3y,
				$p4c1x,$p4y, $p4x,$p4y, $p4x,$p4y,
				$p1x,$p1y);
			$patch_array[1]['colors'] = array($col1,$col2);


			// BOTTOM RIGHT corner
			$p1x = $x00+$w00-$bl;		// control points only matches p3 preceding
			$p1y = $y00+$h00-$d1-$brbgBR_V;		$p1c2y = $p1y + ($d2+$brbgBR_V)*$mag;
			$p2x = $x00+$w00;					// control point only matches p4 preceding
			$p2y = $y00+$h00-$d1-$brbgBR_V;		$p2c2y = $p2y + ($d1+$brbgBR_V)*$mag;
			$p3x = $x00+$w00-$d1-$brbgBR_H;		$p3c1x = $p3x + ($d1+$brbgBR_H)*$mag;
			$p3y = $y00+$h00;					$p3c2y = $p3y - $bl/2;
			$p4x = $x00+$w00-$d1-$brbgBR_H;		$p4c2x = $p4x + ($d2+$brbgBR_H)*$mag;
			$p4y = $y00+$h00-$bl;

			if (-$d2 > min($brbgBR_H, $brbgBR_V) || $flatten) {
				$p1y = $y00+$h00-$bl;		$p1c2y = $p1y;
				$p2y = $y00+$h00-$bl;		$p2c2y = $p2y + $bl*$mag2;
				$p3x = $x00+$w00-$bl;		$p3c1x = $p3x + $bl*$mag2;
				$p4x = $x00+$w00-$bl;		$p4c2x = $p4x;
			}

			$shadow .= sprintf('%.3F %.3F %.3F %.3F %.3F %.3F c ', ($p1x)*_MPDFK, ($this->h-($p1c2y))*_MPDFK, ($p4c2x)*_MPDFK, ($this->h-($p4y))*_MPDFK, ($p4x)*_MPDFK, ($this->h-($p4y))*_MPDFK);
			$patch_array[2]['f']=2;
			$patch_array[2]['points']=array($p2x,$p2c2y,
				$p3c1x,$p3y, $p3x,$p3y, $p3x,$p3c2y,
				$p4x,$p4y, $p4x,$p4y, $p4c2x,$p4y,
				$p1x,$p1c2y);
			$patch_array[2]['colors'] = array($col2,$col1);



			// BOTTOM
			$p1x = $x00+$w00-$d1-$brbgBR_H;	// control point only matches p3 preceding
			$p1y = $y00+$h00;
			$p2x = $x00+$w00-$d1-$brbgBR_H;	// control point only matches p4 preceding
			$p2y = $y00+$h00-$bl;
			$p3x = $x00+$d1+$brbgBL_H;
			$p3y = $y00+$h00-$bl;
			$p4x = $x00+$d1+$brbgBL_H;
			$p4y = $y00+$h00;		$p4c1y = $p4y - $bl/2;

			if (-$d2 > min($brbgBR_H, $brbgBR_V) || $flatten) {
				$p1x = $x00+$w00-$bl;
				$p2x = $x00+$w00-$bl;
			}
			if (-$d2 > min($brbgBL_H, $brbgBL_V) || $flatten) {
				$p3x = $x00+$bl;
				$p4x = $x00+$bl;
			}

			$shadow .= sprintf('%.3F %.3F l ', ($p3x )*_MPDFK, ($this->h-($p3y ))*_MPDFK);
			$patch_array[3]['f']=2;
			$patch_array[3]['points']=array($p2x,$p2y,
				$p3x,$p3y, $p3x,$p3y, $p3x,$p3y,
				$p4x,$p4c1y, $p4x,$p4y, $p4x,$p4y,
				$p1x,$p1y);
			$patch_array[3]['colors'] = array($col1,$col2);

			// BOTTOM LEFT corner
			$p1x = $x00+$d1+$brbgBL_H;		$p1c2x = $p1x - ($d2+$brbgBL_H)*$mag;	// control points only matches p3 preceding
			$p1y = $y00+$h00-$bl;
			$p2x = $x00+$d1+$brbgBL_H;		$p2c2x = $p2x - ($d1+$brbgBL_H)*$mag;	// control point only matches p4 preceding
			$p2y = $y00+$h00;
			$p3x = $x00;				$p3c2x = $p3x + $bl/2;
			$p3y = $y00+$h00-$d1-$brbgBL_V;	$p3c1y = $p3y + ($d1+$brbgBL_V)*$mag;
			$p4x = $x00+$bl;
			$p4y = $y00+$h00-$d1-$brbgBL_V;	$p4c2y = $p4y + ($d2+$brbgBL_V)*$mag;
			if (-$d2 > min($brbgBL_H, $brbgBL_V) || $flatten) {
				$p1x = $x00+$bl;		$p1c2x = $p1x;
				$p2x = $x00+$bl;		$p2c2x = $p2x - $bl*$mag2;
				$p3y = $y00+$h00-$bl;	$p3c1y = $p3y + $bl*$mag2;
				$p4y = $y00+$h00-$bl;	$p4c2y = $p4y;
			}

			$shadow .= sprintf('%.3F %.3F %.3F %.3F %.3F %.3F c ', ($p1c2x)*_MPDFK, ($this->h-($p1y))*_MPDFK, ($p4x)*_MPDFK, ($this->h-($p4c2y))*_MPDFK, ($p4x)*_MPDFK, ($this->h-($p4y))*_MPDFK);
			$patch_array[4]['f']=2;
			$patch_array[4]['points']=array($p2c2x,$p2y,
				$p3x,$p3c1y, $p3x,$p3y, $p3c2x,$p3y,
				$p4x,$p4y, $p4x,$p4y, $p4x,$p4c2y,
				$p1c2x,$p1y);
			$patch_array[4]['colors'] = array($col2,$col1);


			// LEFT - joins on the right (C3-C4 of previous): f = 2
			$p1x = $x00;	// control point only matches p3 preceding
			$p1y = $y00+$h00-$d1-$brbgBL_V;
			$p2x = $x00+$bl;	// control point only matches p4 preceding
			$p2y = $y00+$h00-$d1-$brbgBL_V;
			$p3x = $x00+$bl;
			$p3y = $y00+$d1+$brbgTL_V;
			$p4x = $x00;		$p4c1x = $p4x + $bl/2;
			$p4y = $y00+$d1+$brbgTL_V;
			if (-$d2 > min($brbgBL_H, $brbgBL_V) || $flatten) {
				$p1y = $y00+$h00-$bl;
				$p2y = $y00+$h00-$bl;
			}
			if (-$d2 > min($brbgTL_H, $brbgTL_V) || $flatten) {
				$p3y = $y00+$bl;
				$p4y = $y00+$bl;
			}

			$shadow .= sprintf('%.3F %.3F l ', ($p3x )*_MPDFK, ($this->h-($p3y ))*_MPDFK);
			$patch_array[5]['f']=2;
			$patch_array[5]['points']=array($p2x,$p2y,
				$p3x,$p3y, $p3x,$p3y, $p3x,$p3y,
				$p4c1x,$p4y, $p4x,$p4y, $p4x,$p4y,
				$p1x,$p1y);
			$patch_array[5]['colors'] = array($col1,$col2);

			// TOP LEFT corner
			$p1x = $x00+$bl;		// control points only matches p3 preceding
			$p1y = $y00+$d1+$brbgTL_V;	$p1c2y = $p1y  - ($d2+$brbgTL_V)*$mag;
			$p2x = $x00;			// control point only matches p4 preceding
			$p2y = $y00+$d1+$brbgTL_V;	$p2c2y = $p2y - ($d1+$brbgTL_V)*$mag;
			$p3x = $x00+$d1+$brbgTL_H;	$p3c1x = $p3x - ($d1+$brbgTL_H)*$mag;
			$p3y = $y00;			$p3c2y = $p3y + $bl/2;
			$p4x = $x00+$d1+$brbgTL_H;	$p4c2x = $p4x - ($d2+$brbgTL_H)*$mag;
			$p4y = $y00+$bl;

			if (-$d2 > min($brbgTL_H, $brbgTL_V) || $flatten) {
				$p1y = $y00+$bl;	$p1c2y = $p1y;
				$p2y = $y00+$bl;	$p2c2y = $p2y - $bl*$mag2;
				$p3x = $x00+$bl;	$p3c1x = $p3x - $bl*$mag2;
				$p4x = $x00+$bl;	$p4c2x = $p4x ;
			}

			$shadow .= sprintf('%.3F %.3F %.3F %.3F %.3F %.3F c ', ($p1x)*_MPDFK, ($this->h-($p1c2y))*_MPDFK, ($p4c2x)*_MPDFK, ($this->h-($p4y))*_MPDFK, ($p4x)*_MPDFK, ($this->h-($p4y))*_MPDFK);
			$patch_array[6]['f']=2;
			$patch_array[6]['points']=array($p2x,$p2c2y,
				$p3c1x,$p3y, $p3x,$p3y, $p3x,$p3c2y,
				$p4x,$p4y, $p4x,$p4y, $p4c2x,$p4y,
				$p1x,$p1c2y);
			$patch_array[6]['colors'] = array($col2,$col1);


			// TOP - joins on the right (C3-C4 of previous): f = 2
			$p1x = $x00+$d1+$brbgTL_H;	// control point only matches p3 preceding
			$p1y = $y00;
			$p2x = $x00+$d1+$brbgTL_H;	// control point only matches p4 preceding
			$p2y = $y00+$bl;
			$p3x = $x00+$w00-$d1-$brbgTR_H;
			$p3y = $y00+$bl;
			$p4x = $x00+$w00-$d1-$brbgTR_H;
			$p4y = $y00;		$p4c1y = $p4y + $bl/2;
			if (-$d2 > min($brbgTL_H, $brbgTL_V) || $flatten) {
				$p1x = $x00+$bl;
				$p2x = $x00+$bl;
			}
			if (-$d2 > min($brbgTR_H, $brbgTR_V) || $flatten) {
				$p3x = $x00+$w00-$bl;
				$p4x = $x00+$w00-$bl;
			}

			$shadow .= sprintf('%.3F %.3F l ', ($p3x )*_MPDFK, ($this->h-($p3y ))*_MPDFK);
			$patch_array[7]['f']=2;
			$patch_array[7]['points']=array($p2x,$p2y,
				$p3x,$p3y, $p3x,$p3y, $p3x,$p3y,
				$p4x,$p4c1y, $p4x,$p4y, $p4x,$p4y,
				$p1x,$p1y);
			$patch_array[7]['colors'] = array($col1,$col2);

			$shadow .= ' h f Q '."\n";	// Close path and Fill the inner solid shadow

			if ($bl) $shadow .= $this->grad->CoonsPatchMesh($x00,$y00,$w00,$h00,$patch_array,$x00,$x00+$w00,$y00,$y00+$h00, $colspace, true);

			if ($sh['x'] || $sh['y']) $shadow .= ' Q'."\n"; 	// Shadow Offset
			$shadow .= ' Q'."\n";	// Ends path no-op & Sets the clipping path

		}
	}

	$s .= ' W n ';	// Ends path no-op & Sets the clipping path

	if ($this->blk[$blvl]['bgcolor']) {
		$this->pageBackgrounds[$blvl][] = array('x'=>$x0, 'y'=>$y0, 'w'=>$w, 'h'=>$h, 'col'=>$this->blk[$blvl]['bgcolorarray'], 'clippath'=>$s, 'visibility'=>$this->visibility, 'shadow'=>$shadow, 'z-index'=>$this->current_layer);	// mPDF 5.6.01
	}
	else 	if ($shadow) {
		$this->pageBackgrounds[$blvl][] = array('shadowonly'=>true, 'col'=>'', 'clippath'=>'', 'visibility'=>$this->visibility, 'shadow'=>$shadow, 'z-index'=>$this->current_layer);	// mPDF 5.6.01
	}


	// Float DIV
	$this->blk[$blvl]['bb_painted'][$this->page] = true;

}




function PaintDivLnBorder($state=0,$blvl=0,$h) {
	// $state = 0 normal; 1 top; 2 bottom; 3 top and bottom

	$this->ColDetails[$this->CurrCol]['bottom_margin'] = $this->y + $h;

	$save_y = $this->y;

	$w = $this->blk[$blvl]['width'];
	$x0 = $this->x;				// left
	$y0 = $this->y;				// top
	$x1 = $this->x + $w;			// bottom
	$y1 = $this->y + $h;			// bottom

	if ($this->blk[$blvl]['border_top'] && ($state==1 || $state==3)) {
		$tbd = $this->blk[$blvl]['border_top'];
		if (isset($tbd['s']) && $tbd['s']) {
			$this->_setBorderLine($tbd);
			$this->y = $y0 + ($tbd['w']/2);
			if ($tbd['style']=='dotted' || $tbd['style']=='dashed') { $this->_setDashBorder($tbd['style'],'',$continuingpage,'T'); }
			$this->Line($x0 + ($tbd['w']/2) , $this->y , $x0 + $w - ($tbd['w']/2), $this->y);
			$this->y += $tbd['w'];
			// Reset Corners and Dash off
			$this->SetLineJoin(2);
			$this->SetLineCap(2);
			$this->SetDash();
		}
	}
	if ($this->blk[$blvl]['border_left']) {
		$tbd = $this->blk[$blvl]['border_left'];
		if (isset($tbd['s']) && $tbd['s']) {
			$this->_setBorderLine($tbd);
			$this->y = $y0 + ($tbd['w']/2);
			if ($tbd['style']=='dotted' || $tbd['style']=='dashed') { $this->_setDashBorder($tbd['style'],'',$continuingpage,'L'); }
			$this->Line($x0 + ($tbd['w']/2), $this->y, $x0 + ($tbd['w']/2), $y0 + $h -($tbd['w']/2));
			$this->y += $tbd['w'];
			// Reset Corners and Dash off
			$this->SetLineJoin(2);
			$this->SetLineCap(2);
			$this->SetDash();
		}
	}
	if ($this->blk[$blvl]['border_right']) {
		$tbd = $this->blk[$blvl]['border_right'];
		if (isset($tbd['s']) && $tbd['s']) {
			$this->_setBorderLine($tbd);
		 	$this->y = $y0 + ($tbd['w']/2);
			if ($tbd['style']=='dotted' || $tbd['style']=='dashed') { $this->_setDashBorder($tbd['style'],'',$continuingpage,'R'); }
			$this->Line($x0 + $w - ($tbd['w']/2), $this->y, $x0 + $w - ($tbd['w']/2), $y0 + $h - ($tbd['w']/2));
			$this->y += $tbd['w'];
			// Reset Corners and Dash off
			$this->SetLineJoin(2);
			$this->SetLineCap(2);
			$this->SetDash();
		}
	}
	if ($this->blk[$blvl]['border_bottom'] && $state > 1) {
		$tbd = $this->blk[$blvl]['border_bottom'];
		if (isset($tbd['s']) && $tbd['s']) {
			$this->_setBorderLine($tbd);
			$this->y = $y0 + $h - ($tbd['w']/2);
			if ($tbd['style']=='dotted' || $tbd['style']=='dashed') { $this->_setDashBorder($tbd['style'],'',$continuingpage,'B'); }
			$this->Line($x0 + ($tbd['w']/2) , $this->y, $x0 + $w - ($tbd['w']/2), $this->y);
			$this->y += $tbd['w'];
			// Reset Corners and Dash off
			$this->SetLineJoin(2);
			$this->SetLineCap(2);
			$this->SetDash();
		}
	}
	$this->SetDash();
	$this->y = $save_y;
}


function PaintImgBorder($objattr,$is_table) {
	// Borders are disabled in columns - messes up the repositioning in printcolumnbuffer
	if ($is_table) { $k = $this->shrin_k; } else { $k = 1; }
	$h = (isset($objattr['BORDER-HEIGHT']) ? $objattr['BORDER-HEIGHT'] : 0);
	$w = (isset($objattr['BORDER-WIDTH']) ? $objattr['BORDER-WIDTH'] : 0);
	$x0 = (isset($objattr['BORDER-X']) ? $objattr['BORDER-X'] : 0);
	$y0 = (isset($objattr['BORDER-Y']) ? $objattr['BORDER-Y'] : 0);

	// BORDERS
	if ($objattr['border_top']) {
		$tbd = $objattr['border_top'];
		if (!empty($tbd['s'])) {
			$this->_setBorderLine($tbd,$k);
			if ($tbd['style']=='dotted' || $tbd['style']=='dashed') { $this->_setDashBorder($tbd['style'],'','','T'); }
			$this->Line($x0, $y0, $x0 + $w, $y0);
			// Reset Corners and Dash off
			$this->SetLineJoin(2);
			$this->SetLineCap(2);
			$this->SetDash();
		}
	}
	if ($objattr['border_left']) {
		$tbd = $objattr['border_left'];
		if (!empty($tbd['s'])) {
			$this->_setBorderLine($tbd,$k);
			if ($tbd['style']=='dotted' || $tbd['style']=='dashed') { $this->_setDashBorder($tbd['style'],'','','L'); }
			$this->Line($x0, $y0, $x0, $y0 + $h);
			// Reset Corners and Dash off
			$this->SetLineJoin(2);
			$this->SetLineCap(2);
			$this->SetDash();
		}
	}
	if ($objattr['border_right']) {
		$tbd = $objattr['border_right'];
		if (!empty($tbd['s'])) {
			$this->_setBorderLine($tbd,$k);
			if ($tbd['style']=='dotted' || $tbd['style']=='dashed') { $this->_setDashBorder($tbd['style'],'','','R'); }
			$this->Line($x0 + $w, $y0, $x0 + $w, $y0 + $h);
			// Reset Corners and Dash off
			$this->SetLineJoin(2);
			$this->SetLineCap(2);
			$this->SetDash();
		}
	}
	if ($objattr['border_bottom']) {
		$tbd = $objattr['border_bottom'];
		if (!empty($tbd['s'])) {
			$this->_setBorderLine($tbd,$k);
			if ($tbd['style']=='dotted' || $tbd['style']=='dashed') { $this->_setDashBorder($tbd['style'],'','','B'); }
			$this->Line($x0, $y0 + $h, $x0 + $w, $y0 + $h);
			// Reset Corners and Dash off
			$this->SetLineJoin(2);
			$this->SetLineCap(2);
			$this->SetDash();
		}
	}
	$this->SetDash();
	$this->SetAlpha(1);
}





function Reset() {
	$this->SetTColor($this->ConvertColor(0));
	$this->SetDColor($this->ConvertColor(0));
	$this->SetFColor($this->ConvertColor(255));
	$this->SetAlpha(1);
	$this->colorarray = '';

	$this->spanbgcolorarray = '';
	$this->spanbgcolor = false;
	$this->spanborder = false;
	$this->spanborddet = array();

	$this->ResetStyles();

	$this->HREF = '';
	$this->outlineparam = array();
      $this->outline_on = false;
	$this->SetTextOutline(false);

	$this->SUP = false;
	$this->SUB = false;
	$this->strike = false;
	$this->textshadow = '';

	$this->SetFont($this->default_font,'',0,false);
	$this->SetFontSize($this->default_font_size,false);

	$this->currentfontfamily = '';
	$this->currentfontsize = '';

	if ($this->tableLevel) {
		$this->SetLineHeight('',$this->table_lineheight);	// *TABLES*
	}
	else

	if ($this->listlvl && $this->list_lineheight[$this->listlvl][$this->bulletarray['occur']]) {
		$this->SetLineHeight('',$this->list_lineheight[$this->listlvl][$this->bulletarray['occur']]);	// sets default line height
	}
	else
	if (isset($this->blk[$this->blklvl]['line_height']) && $this->blk[$this->blklvl]['line_height']) {
		$this->SetLineHeight('',$this->blk[$this->blklvl]['line_height']);	// sets default line height
	}

	$this->toupper = false;
	$this->tolower = false;
	$this->kerning = false;
	$this->lSpacingCSS = '';
	$this->wSpacingCSS = '';
	$this->fixedlSpacing = false;
	$this->minwSpacing = 0;
	$this->capitalize = false;
	$this->SetDash(); //restore to no dash
	$this->dash_on = false;
	$this->dotted_on = false;
	$this->divwidth = 0;
	$this->divheight = 0;
	$this->divalign = '';
	$this->divrevert = false;
	$this->oldy = -1;

	$bodystyle = array();
	if (isset($this->CSS['BODY']['FONT-STYLE'])) { $bodystyle['FONT-STYLE'] = $this->CSS['BODY']['FONT-STYLE']; }
	if (isset($this->CSS['BODY']['FONT-WEIGHT'])) { $bodystyle['FONT-WEIGHT'] = $this->CSS['BODY']['FONT-WEIGHT']; }
	if (isset($this->CSS['BODY']['COLOR'])) { $bodystyle['COLOR'] = $this->CSS['BODY']['COLOR']; }
	if (isset($bodystyle)) { $this->setCSS($bodystyle,'BLOCK','BODY'); }

}

function ReadMetaTags($html) {
	// changes anykey=anyvalue to anykey="anyvalue" (only do this when this happens inside tags)
	$regexp = '/ (\\w+?)=([^\\s>"]+)/si';
 	$html = preg_replace($regexp," \$1=\"\$2\"",$html);
	if (preg_match('/<title>(.*?)<\/title>/si',$html,$m)) {
		$this->SetTitle($m[1]);
	}
	preg_match_all('/<meta [^>]*?(name|content)="([^>]*?)" [^>]*?(name|content)="([^>]*?)".*?>/si',$html,$aux);
	$firstattr = $aux[1];
	$secondattr = $aux[3];
	for( $i = 0 ; $i < count($aux[0]) ; $i++) {

		$name = ( strtoupper($firstattr[$i]) == "NAME" )? strtoupper($aux[2][$i]) : strtoupper($aux[4][$i]);
		$content = ( strtoupper($firstattr[$i]) == "CONTENT" )? $aux[2][$i] : $aux[4][$i];
		switch($name) {
			case "KEYWORDS": $this->SetKeywords($content); break;
			case "AUTHOR": $this->SetAuthor($content); break;
			case "DESCRIPTION": $this->SetSubject($content); break;
		}
	}
}


function ReadCharset($html) {
	// Charset conversion
	if ($this->allow_charset_conversion) {
	   if (preg_match('/<head.*charset=([^\'\"\s]*).*<\/head>/si',$html,$m)) {
		if (strtoupper($m[1]) != 'UTF-8') {
			$this->charset_in = strtoupper($m[1]);
		}
	   }
	}
}

//////////////////
/// CSS parser ///
//////////////////
//////////////////
/// CSS parser ///
//////////////////
//////////////////
/// CSS parser ///
//////////////////

function ReadDefaultCSS($CSSstr) {
	$CSS = array();
	$CSSstr = preg_replace('|/\*.*?\*/|s',' ',$CSSstr);
	$CSSstr = preg_replace('/[\s\n\r\t\f]/s',' ',$CSSstr);
	$CSSstr = preg_replace('/(<\!\-\-|\-\->)/s',' ',$CSSstr);
	if ($CSSstr ) {
		preg_match_all('/(.*?)\{(.*?)\}/',$CSSstr,$styles);
		for($i=0; $i < count($styles[1]) ; $i++)  {
			$stylestr= trim($styles[2][$i]);
			$stylearr = explode(';',$stylestr);
			foreach($stylearr AS $sta) {
				if (trim($sta)) {
					// Changed to allow style="background: url('http://www.bpm1.com/bg.jpg')"
					list($property,$value) = explode(':',$sta,2);
					$property = trim($property);
					$value = preg_replace('/\s*!important/i','',$value);
					$value = trim($value);
					if ($property && ($value || $value==='0')) {
	  					$classproperties[strtoupper($property)] = $value;
					}
				}
			}
			$classproperties = $this->fixCSS($classproperties);
			$tagstr = strtoupper(trim($styles[1][$i]));
			$tagarr = explode(',',$tagstr);
			foreach($tagarr AS $tg) {
				$tags = preg_split('/\s+/',trim($tg));
				$level = count($tags);
				if ($level == 1) {		// e.g. p or .class or #id or p.class or p#id
					$t = trim($tags[0]);
					if ($t) {
						$tag = '';
						if (preg_match('/^('.$this->allowedCSStags.')$/',$t)) { $tag= $t; }
						if ($this->CSS[$tag] && $tag) { $CSS[$tag] = $this->array_merge_recursive_unique($CSS[$tag], $classproperties); }
						else if ($tag) { $CSS[$tag] = $classproperties; }
					}
				}
			}
  			$properties = array();
  			$values = array();
  			$classproperties = array();
		}

	} // end of if
	return $CSS;
}




function ReadCSS($html) {
	preg_match_all('/<style[^>]*media=["\']([^"\'>]*)["\'].*?<\/style>/is',$html,$m);
	for($i=0; $i<count($m[0]); $i++) {
		if ($this->CSSselectMedia && !preg_match('/('.trim($this->CSSselectMedia).'|all)/i',$m[1][$i])) {
			$html = preg_replace('/'.preg_quote($m[0][$i],'/').'/','',$html);
		}
	}
	preg_match_all('/<link[^>]*media=["\']([^"\'>]*)["\'].*?>/is',$html,$m);
	for($i=0; $i<count($m[0]); $i++) {
		if ($this->CSSselectMedia && !preg_match('/('.trim($this->CSSselectMedia).'|all)/i',$m[1][$i])) {
			$html = preg_replace('/'.preg_quote($m[0][$i],'/').'/','',$html);
		}
	}

	// mPDF 5.5.02
	// Remove Comment tags <!-- ... --> inside CSS as <style> in HTML document
	// Remove Comment tags /* ...  */ inside CSS as <style> in HTML document
	// But first, we replace upper and mixed case closing style tag with lower
	// case so we can use str_replace later.
	preg_replace('/<\/style>/i', '</style>', $html);
	preg_match_all('/<style.*?>(.*?)<\/style>/si',$html,$m);
	if (count($m[1])) {
		for($i=0;$i<count($m[1]);$i++) {
			// Remove comment tags
			$sub = preg_replace('/(<\!\-\-|\-\->)/s',' ',$m[1][$i]);
			$sub = '>'.preg_replace('|/\*.*?\*/|s',' ',$sub).'</style>';
			$html = str_replace('>'.$m[1][$i].'</style>', $sub, $html);
		}
	}


	$html = preg_replace('/<!--mpdf/i','',$html);
	$html = preg_replace('/mpdf-->/i','',$html);
	$html = preg_replace('/<\!\-\-.*?\-\->/s',' ',$html);

	$match = 0; // no match for instance
	$regexp = ''; // This helps debugging: showing what is the REAL string being processed
	$CSSext = array();

	//CSS inside external files
	$regexp = '/<link[^>]*rel=["\']stylesheet["\'][^>]*href=["\']([^>"\']*)["\'].*?>/si';
	$x = preg_match_all($regexp,$html,$cxt);
	if ($x) {
		$match += $x;
		$CSSext = $cxt[1];
	}

	$regexp = '/<link[^>]*href=["\']([^>"\']*)["\'][^>]*?rel=["\']stylesheet["\'].*?>/si';
	$x = preg_match_all($regexp,$html,$cxt);
	if ($x) {
		$match += $x;
		$CSSext = array_merge($CSSext,$cxt[1]);
	}

	// look for @import stylesheets
	//$regexp = '/@import url\([\'\"]{0,1}([^\)]*?\.css)[\'\"]{0,1}\)/si';
	$regexp = '/@import url\([\'\"]{0,1}([^\)]*?\.css(\?\S+)?)[\'\"]{0,1}\)/si';
	$x = preg_match_all($regexp,$html,$cxt);
	if ($x) {
		$match += $x;
		$CSSext = array_merge($CSSext,$cxt[1]);
	}

	// look for @import without the url()
	//$regexp = '/@import [\'\"]{0,1}([^;]*?\.css)[\'\"]{0,1}/si';
	$regexp = '/@import [\'\"]{0,1}([^;]*?\.css(\?\S+)?)[\'\"]{0,1}/si';
	$x = preg_match_all($regexp,$html,$cxt);
	if ($x) {
		$match += $x;
		$CSSext = array_merge($CSSext,$cxt[1]);
	}

	$ind = 0;
	$CSSstr = '';

	if (!is_array($this->cascadeCSS)) $this->cascadeCSS = array();

	while($match){
		$path = $CSSext[$ind];
		$this->GetFullPath($path);
		$CSSextblock = $this->_get_file($path);
		if ($CSSextblock) {
			// look for embedded @import stylesheets in other stylesheets
			// and fix url paths (including background-images) relative to stylesheet
			//$regexpem = '/@import url\([\'\"]{0,1}(.*?\.css)[\'\"]{0,1}\)/si';
			$regexpem = '/@import url\([\'\"]{0,1}(.*?\.css(\?\S+)?)[\'\"]{0,1}\)/si';
			$xem = preg_match_all($regexpem,$CSSextblock,$cxtem);
			$cssBasePath = preg_replace('/\/[^\/]*$/','',$path) . '/';
			if ($xem) {
				foreach($cxtem[1] AS $cxtembedded) {
					// path is relative to original stlyesheet!!
					$this->GetFullPath($cxtembedded, $cssBasePath );
					$match++;
					$CSSext[] = $cxtembedded;
				}
			}
			$regexpem = '/(background[^;]*url\s*\(\s*[\'\"]{0,1})([^\)\'\"]*)([\'\"]{0,1}\s*\))/si';
			$xem = preg_match_all($regexpem,$CSSextblock,$cxtem);
			if ($xem) {
				for ($i=0;$i<count($cxtem[0]);$i++) {
					// path is relative to original stlyesheet!!
					$embedded = $cxtem[2][$i];
					if (!preg_match('/^data:image/i', $embedded)) {	// mPDF 5.5.13
						$this->GetFullPath($embedded, $cssBasePath );
						$CSSextblock = preg_replace('/'.preg_quote($cxtem[0][$i],'/').'/', ($cxtem[1][$i].$embedded.$cxtem[3][$i]), $CSSextblock);
					}
				}
			}
			$CSSstr .= ' '.$CSSextblock;
		}
		$match--;
		$ind++;
	} //end of match

	$match = 0; // reset value, if needed
	// CSS as <style> in HTML document
	$regexp = '/<style.*?>(.*?)<\/style>/si';
	$match = preg_match_all($regexp,$html,$CSSblock);
	if ($match) {
		$tmpCSSstr = implode(' ',$CSSblock[1]);
		$regexpem = '/(background[^;]*url\s*\(\s*[\'\"]{0,1})([^\)\'\"]*)([\'\"]{0,1}\s*\))/si';
		$xem = preg_match_all($regexpem,$tmpCSSstr ,$cxtem);
		if ($xem) {
		   for ($i=0;$i<count($cxtem[0]);$i++) {
			$embedded = $cxtem[2][$i];
			if (!preg_match('/^data:image/i', $embedded)) {	// mPDF 5.5.13
				$this->GetFullPath($embedded);
				$tmpCSSstr = preg_replace('/'.preg_quote($cxtem[0][$i],'/').'/', ($cxtem[1][$i].$embedded.$cxtem[3][$i]), $tmpCSSstr );
			}
		   }
		}
		$CSSstr .= ' '.$tmpCSSstr;
	}
	// Remove comments
	$CSSstr = preg_replace('|/\*.*?\*/|s',' ',$CSSstr);
	$CSSstr = preg_replace('/[\s\n\r\t\f]/s',' ',$CSSstr);

	if (preg_match('/@media/',$CSSstr)) {
		preg_match_all('/@media(.*?)\{(([^\{\}]*\{[^\{\}]*\})+)\s*\}/is',$CSSstr,$m);
		for($i=0; $i<count($m[0]); $i++) {
			if ($this->CSSselectMedia && !preg_match('/('.trim($this->CSSselectMedia).'|all)/i',$m[1][$i])) {
				$CSSstr = preg_replace('/'.preg_quote($m[0][$i],'/').'/','',$CSSstr);
			}
			else {
				$CSSstr = preg_replace('/'.preg_quote($m[0][$i],'/').'/',' '.$m[2][$i].' ',$CSSstr);
			}
		}
	}

	// mPDF 5.5.13
	// Replace any background: url(data:image... with temporary image file reference
	preg_match_all("/(url\(data:image\/(jpeg|gif|png);base64,(.*)\))/si", $CSSstr, $idata);
	if (count($idata[0])) {
		for($i=0;$i<count($idata[0]);$i++) {
			$file = _MPDF_TEMP_PATH.'_tempCSSidata'.RAND(1,10000).'_'.$i.'.'.$idata[2][$i];
			//Save to local file
			file_put_contents($file, base64_decode($idata[3][$i]));
			// $this->GetFullPath($file);	// ? is this needed - NO  mPDF 5.6.03
			$CSSstr = str_replace($idata[0][$i], 'url("'.$file.'")', $CSSstr); 	// mPDF 5.5.17
		}
	}

	$CSSstr = preg_replace('/(<\!\-\-|\-\->)/s',' ',$CSSstr);
	if ($CSSstr ) {
		preg_match_all('/(.*?)\{(.*?)\}/',$CSSstr,$styles);
		for($i=0; $i < count($styles[1]) ; $i++)  {
			// SET array e.g. $classproperties['COLOR'] = '#ffffff';
	 		$stylestr= trim($styles[2][$i]);
			$stylearr = explode(';',$stylestr);
			foreach($stylearr AS $sta) {
				if (trim($sta)) {
					// Changed to allow style="background: url('http://www.bpm1.com/bg.jpg')"
					list($property,$value) = explode(':',$sta,2);
					$property = trim($property);
					$value = preg_replace('/\s*!important/i','',$value);
					$value = trim($value);
					if ($property && ($value || $value==='0')) {
					// Ignores -webkit-gradient so doesn't override -moz-
						if ((strtoupper($property)=='BACKGROUND-IMAGE' || strtoupper($property)=='BACKGROUND') && preg_match('/-webkit-gradient/i',$value)) {
							continue;
						}
	  					$classproperties[strtoupper($property)] = $value;
					}
				}
			}
			$classproperties = $this->fixCSS($classproperties);
			$tagstr = strtoupper(trim($styles[1][$i]));
			$tagarr = explode(',',$tagstr);
			$pageselectors = false;	// used to turn on $this->mirrorMargins
			foreach($tagarr AS $tg) {
				$tags = preg_split('/\s+/',trim($tg));
				$level = count($tags);
				$t = '';
				$t2 = '';
				$t3 = '';
				if (trim($tags[0])=='@PAGE') {
					if (isset($tags[0])) { $t = trim($tags[0]); }
					if (isset($tags[1])) { $t2 = trim($tags[1]); }
					if (isset($tags[2])) { $t3 = trim($tags[2]); }
					$tag = '';
					if ($level==1) { $tag = $t; }
					else if ($level==2 && preg_match('/^[:](.*)$/',$t2,$m)) {
						$tag = $t.'>>PSEUDO>>'.$m[1];
						if ($m[1]=='LEFT' || $m[1]=='RIGHT') { $pageselectors = true; }	// used to turn on $this->mirrorMargins
					}
					else if ($level==2) { $tag = $t.'>>NAMED>>'.$t2; }
					else if ($level==3 && preg_match('/^[:](.*)$/',$t3,$m)) {
						$tag = $t.'>>NAMED>>'.$t2.'>>PSEUDO>>'.$m[1];
						if ($m[1]=='LEFT' || $m[1]=='RIGHT') { $pageselectors = true; }	// used to turn on $this->mirrorMargins
					}
					if (isset($this->CSS[$tag]) && $tag) { $this->CSS[$tag] = $this->array_merge_recursive_unique($this->CSS[$tag], $classproperties); }
					else if ($tag) { $this->CSS[$tag] = $classproperties; }
				}

				else if ($level == 1) {		// e.g. p or .class or #id or p.class or p#id
				if (isset($tags[0])) { $t = trim($tags[0]); }
					if ($t) {
						$tag = '';
						if (preg_match('/^[.](.*)$/',$t,$m)) { $tag = 'CLASS>>'.$m[1]; }
						else if (preg_match('/^[#](.*)$/',$t,$m)) { $tag = 'ID>>'.$m[1]; }
						else if (preg_match('/^('.$this->allowedCSStags.')[.](.*)$/',$t,$m)) { $tag = $m[1].'>>CLASS>>'.$m[2]; }
						else if (preg_match('/^('.$this->allowedCSStags.')\s*:NTH-CHILD\((.*)\)$/',$t,$m)) { $tag = $m[1].'>>SELECTORNTHCHILD>>'.$m[2]; }
						else if (preg_match('/^('.$this->allowedCSStags.')[#](.*)$/',$t,$m)) { $tag = $m[1].'>>ID>>'.$m[2]; }
						else if (preg_match('/^('.$this->allowedCSStags.')$/',$t)) { $tag= $t; }
						if (isset($this->CSS[$tag]) && $tag) { $this->CSS[$tag] = $this->array_merge_recursive_unique($this->CSS[$tag], $classproperties); }
						else if ($tag) { $this->CSS[$tag] = $classproperties; }
					}
				}
				else {
					$tmp = array();
					for($n=0;$n<$level;$n++) {
						if (isset($tags[$n])) { $t = trim($tags[$n]); }
						else { $t = ''; }
						if ($t) {
							$tag = '';
							if (preg_match('/^[.](.*)$/',$t,$m)) { $tag = 'CLASS>>'.$m[1]; }
							else if (preg_match('/^[#](.*)$/',$t,$m)) { $tag = 'ID>>'.$m[1]; }
							else if (preg_match('/^('.$this->allowedCSStags.')[.](.*)$/',$t,$m)) { $tag = $m[1].'>>CLASS>>'.$m[2]; }
							else if (preg_match('/^('.$this->allowedCSStags.')\s*:NTH-CHILD\((.*)\)$/',$t,$m)) { $tag = $m[1].'>>SELECTORNTHCHILD>>'.$m[2]; }
							else if (preg_match('/^('.$this->allowedCSStags.')[#](.*)$/',$t,$m)) { $tag = $m[1].'>>ID>>'.$m[2]; }
							else if (preg_match('/^('.$this->allowedCSStags.')$/',$t)) { $tag= $t; }

							if ($tag) $tmp[] = $tag;
							else { break; }
						}
					}
		   
					if ($tag) {
						$x = &$this->cascadeCSS;
						foreach($tmp AS $tp) { $x = &$x[$tp]; }
						$x = $this->array_merge_recursive_unique($x, $classproperties);
						$x['depth'] = $level;
					}
				}
			}
			if ($pageselectors) { $this->mirrorMargins = true; }
  			$properties = array();
  			$values = array();
  			$classproperties = array();
		}
	} // end of if
	//Remove CSS (tags and content), if any
	$regexp = '/<style.*?>(.*?)<\/style>/si'; // it can be <style> or <style type="txt/css">
	$html = preg_replace($regexp,'',$html);
//print_r($this->CSS); exit;
//print_r($this->cascadeCSS); exit;
	return $html;
}

function readInlineCSS($html) {
	//Fix incomplete CSS code
	$size = strlen($html)-1;
	if (substr($html,$size,1) != ';') $html .= ';';
	//Make CSS[Name-of-the-class] = array(key => value)
	$regexp = '|\\s*?(\\S+?):(.+?);|i';
	preg_match_all( $regexp, $html, $styleinfo);
	$properties = $styleinfo[1];
	$values = $styleinfo[2];
	//Array-properties and Array-values must have the SAME SIZE!
	$classproperties = array();
	for($i = 0; $i < count($properties) ; $i++) {
		// Ignores -webkit-gradient so doesn't override -moz-
		if ((strtoupper($properties[$i])=='BACKGROUND-IMAGE' || strtoupper($properties[$i])=='BACKGROUND') && preg_match('/-webkit-gradient/i',$values[$i])) {
			continue;
		}
		$classproperties[strtoupper($properties[$i])] = trim($values[$i]);
	}
	return $this->fixCSS($classproperties);
}



function setCSS($arrayaux,$type='',$tag='') {	// type= INLINE | BLOCK | LIST // tag= BODY
	if (!is_array($arrayaux)) return; //Removes PHP Warning
	// Set font size first so that e.g. MARGIN 0.83em works on font size for this element
	if (isset($arrayaux['FONT-SIZE'])) {
		$v = $arrayaux['FONT-SIZE'];
		if(is_numeric($v[0])) {
			if ($type == 'BLOCK' && $this->blklvl>0 && isset($this->blk[$this->blklvl-1]['InlineProperties']) && isset($this->blk[$this->blklvl-1]['InlineProperties']['size'])) {
				$mmsize = $this->ConvertSize($v,$this->blk[$this->blklvl-1]['InlineProperties']['size']);
			}
			else {
				$mmsize = $this->ConvertSize($v,$this->FontSize);
			}
			$this->SetFontSize( $mmsize*(_MPDFK),false ); //Get size in points (pt)
		}
		else{
  			$v = strtoupper($v);
			if (isset($this->fontsizes[$v])) {
				$this->SetFontSize( $this->fontsizes[$v]* $this->default_font_size,false);
			}
		}
		if ($tag == 'BODY') { $this->SetDefaultFontSize($this->FontSizePt); }
	}


	if ($this->useLang && !$this->usingCoreFont) {
	   if (isset($arrayaux['LANG']) && $arrayaux['LANG'] && $arrayaux['LANG'] != $this->default_lang && ((strlen($arrayaux['LANG']) == 5 && $arrayaux['LANG'] != 'UTF-8') || strlen($arrayaux['LANG']) == 2)) {
		list ($coreSuitable,$mpdf_pdf_unifonts) = GetLangOpts($arrayaux['LANG'], $this->useAdobeCJK);
		if ($mpdf_pdf_unifonts) { $this->RestrictUnicodeFonts($mpdf_pdf_unifonts); }
		else { $this->RestrictUnicodeFonts($this->default_available_fonts ); }
		if ($tag == 'BODY') {
			$this->currentLang = $arrayaux['LANG'];
			$this->default_lang = $arrayaux['LANG'];
			if ($mpdf_pdf_unifonts) { $this->default_available_fonts = $mpdf_pdf_unifonts; }
		}
	   }
	   else {
		$this->RestrictUnicodeFonts($this->default_available_fonts );
	   }
	}

	// FOR INLINE and BLOCK OR 'BODY'
	if (isset($arrayaux['FONT-FAMILY'])) {
		$v = $arrayaux['FONT-FAMILY'];
		//If it is a font list, get all font types
		$aux_fontlist = explode(",",$v);
		$found = 0;
		foreach($aux_fontlist AS $f) {
			$fonttype = trim($f);
			$fonttype = preg_replace('/["\']*(.*?)["\']*/','\\1',$fonttype);
			$fonttype = preg_replace('/ /','',$fonttype);
			$v = strtolower(trim($fonttype));
			if (isset($this->fonttrans[$v]) && $this->fonttrans[$v]) { $v = $this->fonttrans[$v]; }
			if ((!$this->onlyCoreFonts && in_array($v,$this->available_unifonts)) ||
				in_array($v,array('ccourier','ctimes','chelvetica')) ||
				($this->onlyCoreFonts && in_array($v,array('courier','times','helvetica','arial'))) ||
				in_array($v, array('sjis','uhc','big5','gb'))) {
				$fonttype = $v;
				$found = 1;
				break;
			}
		}
		if (!$found) {
		   foreach($aux_fontlist AS $f) {
			$fonttype = trim($f);
			$fonttype = preg_replace('/["\']*(.*?)["\']*/','\\1',$fonttype);
			$fonttype = preg_replace('/ /','',$fonttype);
			$v = strtolower(trim($fonttype));
			if (isset($this->fonttrans[$v]) && $this->fonttrans[$v]) { $v = $this->fonttrans[$v]; }
			if (in_array($v,$this->sans_fonts) || in_array($v,$this->serif_fonts) || in_array($v,$this->mono_fonts) ) {
				$fonttype = $v;
				break;
			}
		   }
		}

		if ($tag == 'BODY') {
			$this->SetDefaultFont($fonttype);
		}
		$this->SetFont($fonttype,$this->currentfontstyle,0,false);
	}
	else {
		$this->SetFont($this->currentfontfamily,$this->currentfontstyle,0,false);
	}

   foreach($arrayaux as $k => $v) {
	if ($type != 'INLINE' && $tag != 'BODY' && $type != 'LIST') {
	  switch($k){
		// BORDERS
		case 'BORDER-TOP':
			$this->blk[$this->blklvl]['border_top'] = $this->border_details($v);
			if ($this->blk[$this->blklvl]['border_top']['s']) { $this->blk[$this->blklvl]['border'] = 1; }
			break;
		case 'BORDER-BOTTOM':
			$this->blk[$this->blklvl]['border_bottom'] = $this->border_details($v);
			if ($this->blk[$this->blklvl]['border_bottom']['s']) { $this->blk[$this->blklvl]['border'] = 1; }
			break;
		case 'BORDER-LEFT':
			$this->blk[$this->blklvl]['border_left'] = $this->border_details($v);
			if ($this->blk[$this->blklvl]['border_left']['s']) { $this->blk[$this->blklvl]['border'] = 1; }
			break;
		case 'BORDER-RIGHT':
			$this->blk[$this->blklvl]['border_right'] = $this->border_details($v);
			if ($this->blk[$this->blklvl]['border_right']['s']) { $this->blk[$this->blklvl]['border'] = 1; }
			break;

		// PADDING
		case 'PADDING-TOP':
			$this->blk[$this->blklvl]['padding_top'] = $this->ConvertSize($v,$this->blk[$this->blklvl-1]['inner_width'],$this->FontSize,false);
			break;
		case 'PADDING-BOTTOM':
			$this->blk[$this->blklvl]['padding_bottom'] = $this->ConvertSize($v,$this->blk[$this->blklvl-1]['inner_width'],$this->FontSize,false);
			break;
		case 'PADDING-LEFT':
			$this->blk[$this->blklvl]['padding_left'] = $this->ConvertSize($v,$this->blk[$this->blklvl-1]['inner_width'],$this->FontSize,false);
			break;
		case 'PADDING-RIGHT':
			$this->blk[$this->blklvl]['padding_right'] = $this->ConvertSize($v,$this->blk[$this->blklvl-1]['inner_width'],$this->FontSize,false);
			break;

		// MARGINS
		case 'MARGIN-TOP':
			$tmp = $this->ConvertSize($v,$this->blk[$this->blklvl-1]['inner_width'],$this->FontSize,false);
			if (isset($this->blk[$this->blklvl]['lastbottommargin'])) {
				if ($tmp > $this->blk[$this->blklvl]['lastbottommargin']) {
					$tmp -= $this->blk[$this->blklvl]['lastbottommargin'];
				}
				else {
					$tmp = 0;
				}
			}
			$this->blk[$this->blklvl]['margin_top'] = $tmp;
			break;
		case 'MARGIN-BOTTOM':
			$this->blk[$this->blklvl]['margin_bottom'] = $this->ConvertSize($v,$this->blk[$this->blklvl-1]['inner_width'],$this->FontSize,false);
			break;
		case 'MARGIN-LEFT':
			$this->blk[$this->blklvl]['margin_left'] = $this->ConvertSize($v,$this->blk[$this->blklvl-1]['inner_width'],$this->FontSize,false);
			break;
		case 'MARGIN-RIGHT':
			$this->blk[$this->blklvl]['margin_right'] = $this->ConvertSize($v,$this->blk[$this->blklvl-1]['inner_width'],$this->FontSize,false);
			break;


		case 'BOX-SHADOW':
			$bs = $this->setCSSboxshadow($v);
			if ($bs) { $this->blk[$this->blklvl]['box_shadow'] = $bs; }
			break;

		case 'BACKGROUND-CLIP':
			if (strtoupper($v) == 'PADDING-BOX') { $this->blk[$this->blklvl]['background_clip'] = 'padding-box'; }
			break;

		case 'PAGE-BREAK-AFTER':
			if (strtoupper($v) == 'AVOID') { $this->blk[$this->blklvl]['page_break_after_avoid'] = true; }
			else if (strtoupper($v) == 'ALWAYS' || strtoupper($v) == 'LEFT' || strtoupper($v) == 'RIGHT') { $this->blk[$this->blklvl]['page_break_after'] = strtoupper($v) ; }
			break;

		case 'WIDTH':
			if (strtoupper($v) != 'AUTO') {
				$this->blk[$this->blklvl]['css_set_width'] = $this->ConvertSize($v,$this->blk[$this->blklvl-1]['inner_width'],$this->FontSize,false);
			}
			break;

		case 'TEXT-INDENT':
			// Left as raw value (may include 1% or 2em)
			$this->blk[$this->blklvl]['text_indent'] = $v;
			break;

	  }//end of switch($k)
	}


	if ($type != 'INLINE' && $type != 'LIST') {	// includes BODY tag
	  switch($k){

		case 'MARGIN-COLLAPSE':	// Custom tag to collapse margins at top and bottom of page
			if (strtoupper($v) == 'COLLAPSE') { $this->blk[$this->blklvl]['margin_collapse'] = true; }
			break;

		case 'LINE-HEIGHT':
			$this->blk[$this->blklvl]['line_height'] = $this->fixLineheight($v);
			if (!$this->blk[$this->blklvl]['line_height'] ) { $this->blk[$this->blklvl]['line_height'] = $this->normalLineheight; }
			break;

		case 'TEXT-ALIGN': //left right center justify
			switch (strtoupper($v)) {
				case 'LEFT':
                        $this->blk[$this->blklvl]['align']="L";
                        break;
				case 'CENTER':
                        $this->blk[$this->blklvl]['align']="C";
                        break;
				case 'RIGHT':
                        $this->blk[$this->blklvl]['align']="R";
                        break;
				case 'JUSTIFY':
                        $this->blk[$this->blklvl]['align']="J";
                        break;
			}
			break;


		case 'DIRECTION':
			if ($v) { $this->blk[$this->blklvl]['direction'] = strtolower($v); }
			break;

	  }//end of switch($k)
	}

	// FOR INLINE ONLY
	if ($type == 'INLINE' || $type == 'LIST') {
	  switch($k){
		case 'DISPLAY':	// Custom tag to collapse margins at top and bottom of page
			if (strtoupper($v) == 'NONE') { $this->inlineDisplayOff = true; }
			break;
		case 'DIRECTION':
			break;
	  }//end of switch($k)
	}
	// FOR INLINE ONLY
	if ($type == 'INLINE') {
	  switch($k){
		// BORDERS
		case 'BORDER-TOP':
			$this->spanborddet['T'] = $this->border_details($v);
			$this->spanborder = true;
			break;
		case 'BORDER-BOTTOM':
			$this->spanborddet['B'] = $this->border_details($v);
			$this->spanborder = true;
			break;
		case 'BORDER-LEFT':
			$this->spanborddet['L'] = $this->border_details($v);
			$this->spanborder = true;
			break;
		case 'BORDER-RIGHT':
			$this->spanborddet['R'] = $this->border_details($v);
			$this->spanborder = true;
			break;
	  }//end of switch($k)
	}


	// FOR INLINE and BLOCK
	  switch($k){
		case 'TEXT-ALIGN': //left right center justify
			if (strtoupper($v) == 'NOJUSTIFY' && $this->blk[$this->blklvl]['align']=="J") {
                        $this->blk[$this->blklvl]['align']="";
			}
			break;
		// bgcolor only - to stay consistent with original html2fpdf
		case 'BACKGROUND':
		case 'BACKGROUND-COLOR':
			$cor = $this->ConvertColor($v);
			if ($cor) {
			   if ($tag  == 'BODY') {
				$this->bodyBackgroundColor = $cor;
			   }
			   else if ($type == 'INLINE' || $type == 'LIST') {
				$this->spanbgcolorarray = $cor;
				$this->spanbgcolor = true;
			   }
			   else {
				$this->blk[$this->blklvl]['bgcolorarray'] = $cor;
				$this->blk[$this->blklvl]['bgcolor'] = true;
			   }
			}
			else if ($type != 'INLINE' && $type != 'LIST') {
  		  		if ($this->ColActive || $this->keep_block_together) {
					$this->blk[$this->blklvl]['bgcolorarray'] = $this->blk[$this->blklvl-1]['bgcolorarray'] ;
					$this->blk[$this->blklvl]['bgcolor'] = $this->blk[$this->blklvl-1]['bgcolor'] ;
				}
			}
			break;

		// auto | normal | none
		case 'FONT-KERNING':
			if ((strtoupper($v) == 'NORMAL' || strtoupper($v) == 'AUTO') && $this->useKerning) { $this->kerning = true; }
			else if (strtoupper($v) == 'NONE') { $this->kerning = false; }
			break;


		// normal | <length>
		case 'LETTER-SPACING':
			$this->lSpacingCSS = $v;
			if (($this->lSpacingCSS || $this->lSpacingCSS==='0') && strtoupper($this->lSpacingCSS) != 'NORMAL') {
				$this->fixedlSpacing = $this->ConvertSize($this->lSpacingCSS,$this->FontSize);
			}
			break;

		// normal | <length>
		case 'WORD-SPACING':
			$this->wSpacingCSS = $v;
			if ($this->wSpacingCSS && strtoupper($this->wSpacingCSS) != 'NORMAL') {
				$this->minwSpacing = $this->ConvertSize($this->wSpacingCSS,$this->FontSize);
			}
			break;

		case 'FONT-STYLE': // italic normal oblique
			switch (strtoupper($v)) {
				case 'ITALIC':
				case 'OBLIQUE':
            			$this->SetStyle('I',true);
					break;
				case 'NORMAL':
            			$this->SetStyle('I',false);
					break;
			}
			break;

		case 'FONT-WEIGHT': // normal bold //Does not support: bolder, lighter, 100..900(step value=100)
			switch (strtoupper($v))	{
				case 'BOLD':
            			$this->SetStyle('B',true);
					break;
				case 'NORMAL':
            			$this->SetStyle('B',false);
					break;
			}
			break;

		case 'VERTICAL-ALIGN': //super and sub only dealt with here e.g. <SUB> and <SUP>
			switch (strtoupper($v)) {
				case 'SUPER':
                        $this->SUP=true;
                        break;
				case 'SUB':
                        $this->SUB=true;
                        break;
			}
			break;

		case 'TEXT-DECORATION': // none underline line-through (strikeout) //Does not support: overline, blink
			if (stristr($v,'LINE-THROUGH')) {
					$this->strike = true;
			}
			if (stristr($v,'UNDERLINE')) {
            			$this->SetStyle('U',true);
			}
			break;

		case 'FONT-VARIANT':
			switch (strtoupper($v)) {
				case 'SMALL-CAPS':
					$this->SetStyle('S',true);
					break;
				case 'NORMAL':
					$this->SetStyle('S',false);
					break;
			}
			break;

		case 'TEXT-TRANSFORM': // none uppercase lowercase //Does support: capitalize
			switch (strtoupper($v)) { //Not working 100%
				case 'CAPITALIZE':
					$this->capitalize=true;
					break;
				case 'UPPERCASE':
					$this->toupper=true;
					break;
				case 'LOWERCASE':
 					$this->tolower=true;
					break;
				case 'NONE': break;
			}
			break;

		case 'TEXT-SHADOW':
			$ts = $this->setCSStextshadow($v);
			if ($ts) { $this->textshadow = $ts; }
			break;

		case 'OUTLINE-WIDTH':
			switch(strtoupper($v)) {
				case 'THIN': $v = '0.03em'; break;
				case 'MEDIUM': $v = '0.05em'; break;
				case 'THICK': $v = '0.07em'; break;
			}
			$this->outlineparam['WIDTH'] = $this->ConvertSize($v,$this->blk[$this->blklvl]['inner_width'],$this->FontSize);
			break;

		case 'OUTLINE-COLOR':
			if (strtoupper($v) == 'INVERT') {
			   if ($this->colorarray) {
				$cor = $this->colorarray;
				$this->outlineparam['COLOR'] = $this->_invertColor($cor);
			   }
			   else {
				$this->outlineparam['COLOR'] = $this->ConvertColor(255);
			   }
			}
			else {
		  	  $cor = $this->ConvertColor($v);
			  if ($cor) { $this->outlineparam['COLOR'] = $cor ; }
			}
			break;

		case 'COLOR': // font color
		  $cor = $this->ConvertColor($v);
			if ($cor) {
				$this->colorarray = $cor;
				$this->SetTColor($cor);
			}
		  break;


	  }//end of switch($k)


   }//end of foreach
}

function SetStyle($tag,$enable) {
	$this->$tag=$enable;
	$style='';
	foreach(array('B','I','U','S') as $s) {
		if($this->$s) {
			$style.=$s;
		}
	}
	if ($this->S && empty($this->upperCase)) { @include(_MPDF_PATH.'includes/upperCase.php'); }
	$this->currentfontstyle=$style;
	$this->SetFont('',$style,0,false);
}

// Set multiple styles at one $str e.g. "BIS"
function SetStylesArray($arr) {
	$style='';
	foreach(array('B','I','U','S') as $s) {
	  if (isset($arr[$s])) {
		if ($arr[$s]) {
			$this->$s = true;
			$style.=$s;
		}
		else { $this->$s = false; }
	  }
	  else if ($this->$s) {	$style.=$s; }
	}
	$this->currentfontstyle=$style;
	$this->SetFont('',$style,0,false);
}

// Set multiple styles at one $str e.g. "BIS"
function SetStyles($str) {
	$style='';
	foreach(array('B','I','U','S') as $s) {
		if (strpos($str,$s) !== false) {
			$this->$s = true;
			$style.=$s;
		}
		else { $this->$s = false; }
	}
	$this->currentfontstyle=$style;
	$this->SetFont('',$style,0,false);
}

function ResetStyles() {
	foreach(array('B','I','U','S') as $s) {
		$this->$s = false;
	}
	$this->currentfontstyle='';
	$this->SetFont('','',0,false);
}


function DisableTags($str='')
{
  if ($str == '') //enable all tags
  {
	//Insert new supported tags in the long string below.
	$this->enabledtags = "<span><s><strike><del><bdo><big><small><ins><cite><acronym><font><sup><sub><b><u><i><a><strong><em><code><samp><tt><kbd><var><q><table><thead><tfoot><tbody><tr><th><td><ol><ul><li><dl><dt><dd><form><input><select><textarea><option><div><p><h1><h2><h3><h4><h5><h6><pre><center><blockquote><address><hr><img><br><indexentry><indexinsert><bookmark><watermarktext><watermarkimage><tts><ttz><tta><column_break><columnbreak><newcolumn><newpage><page_break><pagebreak><formfeed><columns><toc><tocentry><tocpagebreak><pageheader><pagefooter><setpageheader><setpagefooter><sethtmlpageheader><sethtmlpagefooter><annotation><template><jpgraph><barcode><dottab><caption><textcircle><fieldset><legend><article><aside><figure><figcaption><footer><header><hgroup><nav><section><mark><details><summary><meter><progress><time>";	// mPDF 5.5.09
  }
  else
  {
    $str = explode(",",$str);
    foreach($str as $v) $this->enabledtags = str_replace(trim($v),'',$this->enabledtags);
  }
}



function finaliseCellLineHeight($lhxt, $maxfontsize, $maxlineHeight, $lhfixed, $forceExactLineheight) {
	$af = 0; 	// Above font
	$bf = 0; 	// Below font
	$mta = 0;	// Maximum top-aligned
	$mba = 0;	// Maximum bottom-aligned
	if ($lhxt['BS']) {
		$af = max($af, ($lhxt['BS'] - ($maxfontsize * (0.5 + $this->baselineC))));
	}
	if ($lhxt['M']) {
		$af = max($af, ($lhxt['M'] - $maxfontsize)/2);
		$bf = max($bf, ($lhxt['M'] - $maxfontsize)/2);
	}
	if ($lhxt['TT']) {
		$bf = max($bf, ($lhxt['TT'] - $maxfontsize));
	}
	if ($lhxt['TB']) {
		$af = max($af, ($lhxt['TB'] - $maxfontsize));
	}
	if ($lhxt['T']) {
		$mta = max($mta, $lhxt['T']);
	}
	if ($lhxt['B']) {
		$mba = max($mba, $lhxt['B']);
	}
	if ((!$lhfixed || !$forceExactLineheight) && ($af > (($maxlineHeight - $maxfontsize)/2) || $bf > (($maxlineHeight - $maxfontsize)/2))) {
		$maxlineHeight = $maxfontsize + $af + $bf;
	}
	else if (!$lhfixed) { $af = $bf = ($maxlineHeight - $maxfontsize)/2; }
	if ($mta > $maxlineHeight) {
		$bf += ($mta - $maxlineHeight);
		$maxlineHeight = $mta;
	}
	if ($mba > $maxlineHeight) {
		$af += ($mba - $maxlineHeight);
		$maxlineHeight = $mba;
	}
	return $maxlineHeight;
}

function TableWordWrap($maxwidth, $forcewrap = 0, $textbuffer = '', $def_fontsize, $returnarray=false) {	// NB ** returnarray used in flowchart
   $biggestword=0;
   $toonarrow=false;

   $curlyquote = mb_convert_encoding("\xe2\x80\x9e",$this->mb_enc,'UTF-8');
   $curlylowquote = mb_convert_encoding("\xe2\x80\x9d",$this->mb_enc,'UTF-8');
	// mPDF 5.5.07
	$raquote = mb_convert_encoding("\xc2\xbb",$this->mb_enc,'UTF-8');
	// mPDF 5.5.20
	$rsquo = mb_convert_encoding("\xe2\x80\x99",$this->mb_enc,'UTF-8');	//&#8217; &rsquo;
	$sbquo = mb_convert_encoding("\xe2\x80\x9a",$this->mb_enc,'UTF-8');	//&#8218; &sbquo;
	$bdquo = mb_convert_encoding("\xe2\x80\xba",$this->mb_enc,'UTF-8');	//&#8222; &bdquo;

   $textbuffer[0][0] = preg_replace('/^[ ]*/','',$textbuffer[0][0]);

   if ((count($textbuffer) == 0) or ((count($textbuffer) == 1) && ($textbuffer[0][0] == ''))) { return 0; }

   $text = '';
   $lhfixed = false;
   if (preg_match('/([0-9.,]+)mm/',$this->table_lineheight)) { $lhfixed = true; }
   if ($lhfixed) { $def_lineheight = $this->_computeLineheight($this->table_lineheight, $def_fontsize);}
   else { $def_lineheight = 0; }
   // START OF NEW LINE
   // Initialise lineheight variables
   $maxfontsize = 0;
   $forceExactLineheight = true;
   $lhxt = array('BS'=>0, 'M'=>0, 'TT'=>0, 'TB'=>0, 'T'=>0, 'B'=>0);
   $maxlineHeight = $def_lineheight ;
   $ch = 0;
   $width = 0;
   $ln = 1;	// Counts line number
   $mxw = $this->GetCharWidth('W',false);
   foreach ($textbuffer as $cctr=>$chunk) {
	$line = $chunk[0];

	//IMAGE
      if (substr($line,0,3) == "\xbb\xa4\xac") { //identifier has been identified!
		$objattr = $this->_getObjAttr($line);
		if ($objattr['type'] == 'nestedtable') {
			// END OF LINE
			// Finalise & add lineheight
			$ch += $this->finaliseCellLineHeight($lhxt, $maxfontsize, $maxlineHeight, $lhfixed, $forceExactLineheight);
			$level = $objattr['level'];
			$ih = $this->table[($level+1)][$objattr['nestedcontent']]['h'];	// nested table width
			$ch += $ih;
			// START OF NEW LINE
			// Initialise lineheight variables
			$ln++;
			$maxfontsize = 0;
			$forceExactLineheight = true;
			$lhxt = array('BS'=>0, 'M'=>0, 'TT'=>0, 'TB'=>0, 'T'=>0, 'B'=>0);
			$maxlineHeight = $def_lineheight ;
			$width = 0;
			$text = "";
			continue;
		}

		list($skipln,$iw,$ih) = $this->inlineObject((isset($specialcontent['type']) ? $specialcontent['type'] : null),0,0, $objattr, $this->lMargin,$width,$maxwidth,$maxlineHeight,false,true);
		if ($objattr['type'] == 'hr') {
			// END OF LINE
			// Finalise & add lineheight
			$ch += $this->finaliseCellLineHeight($lhxt, $maxfontsize, $maxlineHeight, $lhfixed, $forceExactLineheight);
			// Add HR height
			$ch += $ih;
			// START OF NEW LINE
			// Initialise lineheight variables
			$ln++;
			$maxfontsize = 0;
			$forceExactLineheight = true;
			$lhxt = array('BS'=>0, 'M'=>0, 'TT'=>0, 'TB'=>0, 'T'=>0, 'B'=>0);
			$maxlineHeight = $def_lineheight ;
			$width = 0;
			$text = "";
			continue;
		}

		if ($skipln==1 || $skipln==-2) {
			// Finish last line
			// END OF LINE
			// Finalise & add lineheight
			$ch += $this->finaliseCellLineHeight($lhxt, $maxfontsize, $maxlineHeight, $lhfixed, $forceExactLineheight);
			// START OF NEW LINE
			// Initialise lineheight variables
			$maxfontsize = 0;
			$forceExactLineheight = true;
			$lhxt = array('BS'=>0, 'M'=>0, 'TT'=>0, 'TB'=>0, 'T'=>0, 'B'=>0);
			$maxlineHeight = $def_lineheight ;
			$ln++;
			$width = 0;
			$text = "";
		}
		$va = (isset($objattr['vertical-align']) ? $objattr['vertical-align'] : null);
		if ($va) {
			$lhxt[$va] = max($lhxt[$va], $ih);
		}
		if ($lhfixed && $ih > $def_fontsize) { $forceExactLineheight = false; }
		$maxlineHeight = max($maxlineHeight ,$ih);
		$width += $iw;
		continue;
	}

	// SET FONT SIZE/STYLE from $chunk[n]
	// FONTSIZE
	if(isset($chunk[11]) and $chunk[11] != '') {
	   if ($this->shrin_k) {
		$this->SetFontSize($chunk[11]/$this->shrin_k,false);
	   }
	   else {
		$this->SetFontSize($chunk[11],false);
	   }
	}
	if ($line == "\n") {
		// END OF LINE
		$maxfontsize = max($maxfontsize,$this->FontSize);
		$fh = $this->_computeLineheight($this->table_lineheight);
		if ($lhfixed && $this->FontSize > $def_fontsize) {
			$fh = $this->FontSize;
			$forceExactLineheight = false;
		}
		$maxlineHeight = max($maxlineHeight,$fh);

		// Finalise & add lineheight
		$ch += $this->finaliseCellLineHeight($lhxt, $maxfontsize, $maxlineHeight, $lhfixed, $forceExactLineheight);
		// START OF NEW LINE
		// Initialise lineheight variables
		$maxfontsize = 0;
		$forceExactLineheight = true;
		$lhxt = array('BS'=>0, 'M'=>0, 'TT'=>0, 'TB'=>0, 'T'=>0, 'B'=>0);
		$maxlineHeight = $this->_computeLineheight($this->table_lineheight);
		$ln++;
		$text = "";
		$width = 0;
		if(isset($chunk[11]) and $chunk[11] != '') {
			$this->SetFontSize($this->default_font_size,false);
		}
		continue;
	}

	$lbw = $rbw = 0;	// Border widths
	if(isset($chunk[16]) && !empty($chunk[16])) {	//Border
		$this->spanborddet = $chunk[16];
		if (isset($this->spanborddet['L'])) $lbw = $this->spanborddet['L']['w'];
		if (isset($this->spanborddet['R'])) $rbw = $this->spanborddet['R']['w'];
	}
      if(isset($chunk[15])) { 	 // Word spacing
		$this->wSpacingCSS = $chunk[15];
		if ($this->wSpacingCSS && strtoupper($this->wSpacingCSS) != 'NORMAL') {
			$this->minwSpacing = $this->ConvertSize($this->wSpacingCSS,$this->FontSize);
		}
	}
      if(isset($chunk[14])) { 	 // Letter spacing
		$this->lSpacingCSS = $chunk[14];
		if (($this->lSpacingCSS || $this->lSpacingCSS==='0') && strtoupper($this->lSpacingCSS) != 'NORMAL') {
			$this->fixedlSpacing = $this->ConvertSize($this->lSpacingCSS,$this->FontSize);
		}
	}
      if(isset($chunk[13])) { 	 // Font Kerning
		$this->kerning = $chunk[13];
	}
	// FONTFAMILY
	if(isset($chunk[4]) and $chunk[4] != '') { $font = $this->SetFont($chunk[4],$this->FontStyle,0,false); }

	// FONT STYLE B I U
	if(isset($chunk[2]) and $chunk[2] != '') {
		$this->SetStyles($chunk[2]);
	}

	$space = $this->GetCharWidth(' ',false);

	if (mb_substr($line,0,1,$this->mb_enc ) == ' ') { 	// line (chunk) starts with a space
		$width += $space;
		$text .= ' ';
	}

	if (mb_substr($line,(mb_strlen($line,$this->mb_enc )-1),1,$this->mb_enc ) == ' ') { $lsend = true; }	// line (chunk) ends with a space
	else { $lsend = false; }
	$line= trim($line);
	if ($line == '') { continue; }

	// mPDF ITERATION
	if ($this->iterationCounter) $line = preg_replace('/{iteration ([a-zA-Z0-9_]+)}/','\\1', $line);

	if (!$this->usingCoreFont) { $matchsuff="u"; } else { $matchsuff=""; }	// mPDF.5.5.05

	$words = explode(' ', $line);

	foreach ($words as $k=>$word) {
		$word = trim($word);
		$wordwidth = $this->GetStringWidth($word);
		if ($k==0) { $wordwidth += $lbw; }
		if ($k==(count($words)-1)) { $wordwidth += $rbw; }
		//maxwidth is insufficient for one word
		if ($wordwidth > $maxwidth + 0.0001) {
			$firstchunk=true;
			while($wordwidth > $maxwidth + 0.0001) {
				$chw = 0;	// check width
				$oneCJKorphan = false;
				$mlen = mb_strlen($word, $this->mb_enc );
				for ( $i = 0; $i < $mlen; $i++ ) {
					$chw = $this->GetStringWidth(mb_substr($word,0,$i+1,$this->mb_enc ));
					if ($k==0) { $chw += $lbw; }
					if ($k==(count($words)-1)) { $chw += $rbw; }
					if ($chw > $maxwidth) {
						if ($i==0 && $firstchunk) {
							// If first letter of line does not fit
							$wordwidth = $maxwidth - 0.0001;
							if ($this->debug) { $this->Error("Table cell width calculated less than that needed for one character!"); }
							break;
						}
						if (!$oneCJKorphan && (preg_match('/[\.\]),;:!?"'.$curlyquote . $curlylowquote . $raquote.$rsquo.$sbquo.$bdquo.']$/'.$matchsuff,mb_substr($word,0,$i+1,$this->mb_enc )) || (!$this->usingCoreFont && preg_match('/['.$this->CJKoverflow.']$/u',mb_substr($word,0,$i+1,$this->mb_enc ))))) {	// mPDF.5.5.01  mPDF.5.5.05 mPDF 5.5.07 // mPDF 5.5.20
							$wordwidth = $maxwidth - 0.0001;
							$oneCJKorphan = true;
							continue;
						}
						if ($text && $firstchunk) {
							// END OF LINE
							// Finalise & add lineheight
							$maxfontsize = max($maxfontsize,$this->FontSize);
							$fh = $this->_computeLineheight($this->table_lineheight);
							if ($lhfixed && $this->FontSize > $def_fontsize) {
								$fh = $this->FontSize;
								$forceExactLineheight = false;
							}
							$maxlineHeight = max($maxlineHeight,$fh);
							$ch += $this->finaliseCellLineHeight($lhxt, $maxfontsize, $maxlineHeight, $lhfixed, $forceExactLineheight);
							// START OF NEW LINE
							// Initialise lineheight variables
							$maxfontsize = $this->FontSize;
							$forceExactLineheight = true;
							$lhxt = array('BS'=>0, 'M'=>0, 'TT'=>0, 'TB'=>0, 'T'=>0, 'B'=>0);
							$maxlineHeight = $this->_computeLineheight($this->table_lineheight);
							$ln++;
						}
						// END OF LINE
						// Finalise & add lineheight
						$maxfontsize = max($maxfontsize,$this->FontSize);
						$fh = $this->_computeLineheight($this->table_lineheight);
						if ($lhfixed && $this->FontSize > $def_fontsize) {
							$fh = $this->FontSize;
							$forceExactLineheight = false;
						}
						$maxlineHeight = max($maxlineHeight,$fh);
						$ch += $this->finaliseCellLineHeight($lhxt, $maxfontsize, $maxlineHeight, $lhfixed, $forceExactLineheight);
						// START OF NEW LINE
						// Initialise lineheight variables
						$maxfontsize = $this->FontSize;
						$forceExactLineheight = true;
						$lhxt = array('BS'=>0, 'M'=>0, 'TT'=>0, 'TB'=>0, 'T'=>0, 'B'=>0);
						$maxlineHeight = $this->_computeLineheight($this->table_lineheight);
						$ln++;
						$mxw = $maxwidth;
						$text = mb_substr($word,0,$i,$this->mb_enc );
						$word = mb_substr($word,$i,mb_strlen($word, $this->mb_enc )-$i,$this->mb_enc );
						$wordwidth = $this->GetStringWidth($word);
						$width = 0;
						$firstchunk=false;
						break;
					}
				}
				if (mb_strlen($word, $this->mb_enc )<2 && $wordwidth > $maxwidth + 0.0001) {
					$wordwidth = $maxwidth - 0.0001;
					if ($this->debug) { $this->Error("Table cell width calculated less than that needed for single character!"); }
				}
				$firstchunk=false;
			}
		}
		// Word fits on line...
		if ($width + $wordwidth  < $maxwidth + 0.0001) {
			$mxw = max($mxw, ($width+$wordwidth));
			$width += $wordwidth + $space;
			$text .= $word.' ';
		}
		// Word does not fit on line...
		else {
			$alloworphans = false;
			// In case of orphan punctuation or SUB/SUP
			// Strip end punctuation
			$tmp = preg_replace('/[\.\]),;:!?"'.$curlyquote . $curlylowquote . $raquote.$rsquo.$sbquo.$bdquo.']{1}$/'.$matchsuff,'',$word);	// mPDF.5.5.01  mPDF.5.5.05 mPDF 5.5.07  // mPDF 5.5.20
			if ($tmp !== $word && (($this->usingCoreFont && !preg_match('/[\.\]),;:!?"'.$curlyquote . $curlylowquote . $raquote.$rsquo.$sbquo.$bdquo.']$/',$tmp)) || (!$this->usingCoreFont && !preg_match('/[\.\]),;:!?"'.$curlyquote . $curlylowquote . $raquote.$rsquo.$sbquo.$bdquo .$this->CJKoverflow.']$/u',$tmp)))) {	// mPDF 5.5.07  // mPDF 5.5.20
				$tmpwidth = $this->GetStringWidth($tmp);
				if ($k==0) { $tmpwidth += $lbw; }
				if ($k==(count($words)-1)) { $tmpwidth += $rbw; }
				if ($width + $tmpwidth  < $maxwidth + 0.0001) { $alloworphans = true; }
			}
			// If line = SUB/SUP to max of orphansallowed ( $this->SUP || $this->SUB )
			if(( (isset($chunk[5]) and $chunk[5]) || (isset($chunk[6]) and $chunk[6])) && strlen($word) <= $this->orphansAllowed) {
				$alloworphans = true;
if ($k==1) { echo $word; exit; }
			}


			// if [stripped] word fits
			if ($alloworphans) {
				$mxw = $maxwidth;
				$width += $wordwidth + $space;
				$text .= $word.' ';
			}
			else {
				// END OF LINE
				// Finalise & add lineheight
				$maxfontsize = max($maxfontsize,$this->FontSize);
				$fh = $this->_computeLineheight($this->table_lineheight);
				if ($lhfixed && $this->FontSize > $def_fontsize) {
					$fh = $this->FontSize;
					$forceExactLineheight = false;
				}
				$maxlineHeight = max($maxlineHeight,$fh);
				$ch += $this->finaliseCellLineHeight($lhxt, $maxfontsize, $maxlineHeight, $lhfixed, $forceExactLineheight);
				$mxw = $maxwidth;
				// START OF NEW LINE
				// Initialise lineheight variables
				$maxfontsize = $this->FontSize;
				$forceExactLineheight = true;
				$lhxt = array('BS'=>0, 'M'=>0, 'TT'=>0, 'TB'=>0, 'T'=>0, 'B'=>0);
				$maxlineHeight = $this->_computeLineheight($this->table_lineheight);
				$ln++;
				$width = $wordwidth + $space;
				$text = $word.' ';
			}
          }
		$maxfontsize = max($maxfontsize,$this->FontSize);
		$fh = $this->_computeLineheight($this->table_lineheight);
		if ($lhfixed && $this->FontSize > $def_fontsize) {
			$fh = $this->FontSize;
			$forceExactLineheight = false;
		}
		$maxlineHeight = max($maxlineHeight,$fh);
	}

	// End of textbuffer chunk
	if (!$lsend) {
		$width -= $space;
		$text = rtrim($text);
	}

	// RESET FONT SIZE/STYLE
	// RESETTING VALUES
	//Now we must deactivate what we have used
	if(isset($chunk[2]) and $chunk[2] != '') {
		$this->ResetStyles();
	}
	if(isset($chunk[4]) and $chunk[4] != '') {
		$this->SetFont($this->default_font,$this->FontStyle,0,false);
	}
	if(isset($chunk[11]) and $chunk[11] != '') {
		$this->SetFontSize($this->default_font_size,false);
	}
	$this->spanborddet = array();
	$this->kerning = false;
	$this->lSpacingCSS = '';
	$this->wSpacingCSS = '';
	$this->fixedlSpacing = false;
	$this->minwSpacing = 0;
   }
   // Finalise lineheight if something output on line and add
   if ($width) {
	$ch += $this->finaliseCellLineHeight($lhxt, $maxfontsize, $maxlineHeight, $lhfixed, $forceExactLineheight);
   }
   if ($returnarray) { return array($ch,$ln,$mxw); }
   else { return $ch; }

}


function TableCheckMinWidth($maxwidth, $forcewrap = 0, $textbuffer) {
	$biggestword=0;
	$toonarrow=false;
	if ((count($textbuffer) == 0) or ((count($textbuffer) == 1) && ($textbuffer[0][0] == ''))) { return 0; }

	foreach ($textbuffer as $chunk) {

		$line = $chunk[0];
		// mPDF ITERATION
		if ($this->iterationCounter) $line = preg_replace('/{iteration ([a-zA-Z0-9_]+)}/','\\1', $line);

		// IMAGES & FORM ELEMENTS
      	if (substr($line,0,3) == "\xbb\xa4\xac") { //inline object - FORM element or IMAGE!
			$objattr = $this->_getObjAttr($line);
			if ($objattr['type']!='hr' && isset($objattr['width']) && ($objattr['width']/$this->shrin_k) > ($maxwidth + 0.0001) ) {
				if (($objattr['width']/$this->shrin_k) > $biggestword) { $biggestword = ($objattr['width']/$this->shrin_k); }
				$toonarrow=true;
			}
			continue;
		}

		if ($line == "\n") {
			continue;
		}
    		$line = trim($line );
		// SET FONT SIZE/STYLE from $chunk[n]

		// FONTSIZE
	      if(isset($chunk[11]) and $chunk[11] != '') {
		   if ($this->shrin_k) {
			$this->SetFontSize($chunk[11]/$this->shrin_k,false);
		   }
		   else {
			$this->SetFontSize($chunk[11],false);
		   }
		}
		// FONTFAMILY
	      if(isset($chunk[4]) and $chunk[4] != '') { $font = $this->SetFont($chunk[4],$this->FontStyle,0,false); }
		// B I U
	      if(isset($chunk[2]) and $chunk[2] != '') {
			$this->SetStyles($chunk[2]);
	      }

		$lbw = $rbw = 0;	// Border widths
		if(isset($chunk[16]) && !empty($chunk[16])) { //Border
			$this->spanborddet = $chunk[16];
			$lbw = $this->spanborddet['L']['w'];
			$rbw = $this->spanborddet['R']['w'];
		}
	      if(isset($chunk[15])) { 	 // Word spacing
			$this->wSpacingCSS = $chunk[15];
			if ($this->wSpacingCSS && strtoupper($this->wSpacingCSS) != 'NORMAL') {
				$this->minwSpacing = $this->ConvertSize($this->wSpacingCSS,$this->FontSize);
			}
		}
	      if(isset($chunk[14])) { 	 // Letter spacing
			$this->lSpacingCSS = $chunk[14];
			if (($this->lSpacingCSS || $this->lSpacingCSS==='0') && strtoupper($this->lSpacingCSS) != 'NORMAL') {
				$this->fixedlSpacing = $this->ConvertSize($this->lSpacingCSS,$this->FontSize);
			}
		}
	      if(isset($chunk[13])) { 	 // Font Kerning
			$this->kerning = $chunk[13];
		}

		$words = explode(' ', $line);
		foreach ($words as $k=>$word) {
			$word = trim($word);
			$wordwidth = $this->GetStringWidth($word);
			if ($k==0) { $wordwidth += $lbw; }
			if ($k==(count($words)-1)) { $wordwidth += $rbw; }

			//Warn user that maxwidth is insufficient
			if ($wordwidth > $maxwidth + 0.0001) {
				if ($wordwidth > $biggestword) { $biggestword = $wordwidth; }
				$toonarrow=true;
			}
		}

		// RESET FONT SIZE/STYLE
		// RESETTING VALUES
		//Now we must deactivate what we have used
		if(isset($chunk[2]) and $chunk[2] != '') {
			$this->ResetStyles();
		}
		if(isset($chunk[4]) and $chunk[4] != '') {
			$this->SetFont($this->default_font,$this->FontStyle,0,false);
		}
		if(isset($chunk[11]) and $chunk[11] != '') {
			$this->SetFontSize($this->default_font_size,false);
		}
		$this->spanborddet = array();
		$this->kerning = false;
		$this->lSpacingCSS = '';
		$this->wSpacingCSS = '';
		$this->fixedlSpacing = false;
		$this->minwSpacing = 0;
	}

	//Return -(wordsize) if word is bigger than maxwidth
	// ADDED
      if (($toonarrow) && ($this->table_error_report)) {
		$this->Error("Word is too long to fit in table - ".$this->table_error_report_param);
	}
	if ($toonarrow) return -$biggestword;
	else return 1;
}

function shrinkTable(&$table,$k) {
 		$table['border_spacing_H'] /= $k;
 		$table['border_spacing_V'] /= $k;

		$table['padding']['T'] /= $k;
		$table['padding']['R'] /= $k;
		$table['padding']['B'] /= $k;
		$table['padding']['L'] /= $k;

		$table['margin']['T'] /= $k;
		$table['margin']['R'] /= $k;
		$table['margin']['B'] /= $k;
		$table['margin']['L'] /= $k;

		$table['border_details']['T']['w'] /= $k;
		$table['border_details']['R']['w'] /= $k;
		$table['border_details']['B']['w'] /= $k;
		$table['border_details']['L']['w'] /= $k;

		if (isset($table['max_cell_border_width']['T'])) $table['max_cell_border_width']['T'] /= $k;
		if (isset($table['max_cell_border_width']['R'])) $table['max_cell_border_width']['R'] /= $k;
		if (isset($table['max_cell_border_width']['B'])) $table['max_cell_border_width']['B'] /= $k;
		if (isset($table['max_cell_border_width']['L'])) $table['max_cell_border_width']['L'] /= $k;

		if ($this->simpleTables){
			$table['simple']['border_details']['T']['w'] /= $k;
			$table['simple']['border_details']['R']['w'] /= $k;
			$table['simple']['border_details']['B']['w'] /= $k;
			$table['simple']['border_details']['L']['w'] /= $k;
		}

		$table['miw'] /= $k;
		$table['maw'] /= $k;

		if ($this->cacheTables) { $fh = fopen($table['cache'], "r+b"); }

		for($j = 0 ; $j < $table['nc'] ; $j++ ) { //columns

		   $table['wc'][$j]['miw'] /= $k;
		   $table['wc'][$j]['maw'] /= $k;

		   if (isset($table['wc'][$j]['absmiw']) && $table['wc'][$j]['absmiw'] ) $table['wc'][$j]['absmiw'] /= $k;

		   for($i = 0 ; $i < $table['nr']; $i++ ) { //rows
			if ($this->cacheTables) {
				$c = $this->_uncacheCell($table['cells'][$i][$j], '', $fh);
			}
			else
				$c = &$table['cells'][$i][$j];
			if (isset($c) && $c)  {
				if (!$this->simpleTables){
				  if ($this->packTableData) {
					$cell = $this->_unpackCellBorder($c['borderbin'] );
					$cell['border_details']['T']['w'] /= $k;
					$cell['border_details']['R']['w'] /= $k;
					$cell['border_details']['B']['w'] /= $k;
					$cell['border_details']['L']['w'] /= $k;
					$cell['border_details']['mbw']['TL'] /= $k;
					$cell['border_details']['mbw']['TR'] /= $k;
					$cell['border_details']['mbw']['BL'] /= $k;
					$cell['border_details']['mbw']['BR'] /= $k;
					$cell['border_details']['mbw']['LT'] /= $k;
					$cell['border_details']['mbw']['LB'] /= $k;
					$cell['border_details']['mbw']['RT'] /= $k;
					$cell['border_details']['mbw']['RB'] /= $k;
					$c['borderbin'] = $this->_packCellBorder($cell);
				  }
				  else {
					$c['border_details']['T']['w'] /= $k;
					$c['border_details']['R']['w'] /= $k;
					$c['border_details']['B']['w'] /= $k;
					$c['border_details']['L']['w'] /= $k;
					$c['border_details']['mbw']['TL'] /= $k;
					$c['border_details']['mbw']['TR'] /= $k;
					$c['border_details']['mbw']['BL'] /= $k;
					$c['border_details']['mbw']['BR'] /= $k;
					$c['border_details']['mbw']['LT'] /= $k;
					$c['border_details']['mbw']['LB'] /= $k;
					$c['border_details']['mbw']['RT'] /= $k;
					$c['border_details']['mbw']['RB'] /= $k;
				  }
				}
				$c['padding']['T'] /= $k;
				$c['padding']['R'] /= $k;
				$c['padding']['B'] /= $k;
				$c['padding']['L'] /= $k;
				$c['maxs'] /= $k;
				if (isset($c['w'])) { $c['w'] /= $k; }
				$c['s'] /= $k;
				$c['maw'] /= $k;
				$c['miw'] /= $k;
				if (isset($c['absmiw'])) $c['absmiw'] /= $k;
				if (isset($c['nestedmaw'])) $c['nestedmaw'] /= $k;
				if (isset($c['nestedmiw'])) $c['nestedmiw'] /= $k;
				if (isset($c['textbuffer'])) {
					foreach($c['textbuffer'] AS $n=>$tb) {
						if (!empty($tb[16])) {
							$c['textbuffer'][$n][16]['T']['w'] /= $k;
							$c['textbuffer'][$n][16]['B']['w'] /= $k;
							$c['textbuffer'][$n][16]['L']['w'] /= $k;
							$c['textbuffer'][$n][16]['R']['w'] /= $k;
						}
					}
				}
				if ($this->cacheTables) {
					$this->_cacheCell($c, '', "W", $fh, $table['cells'][$i][$j]);
				}
				unset($c);
			}
		   }//rows
		}//columns
		if ($this->cacheTables) { fclose($fh); }
}

function _cacheCell($c, $file, $mode="A", $fh=null, $offset=0) {	// mode = Append or (over)Write (needs offset and fh)
	// Requires either $file OR $fh (file_handle)
	if (!is_array($c) || !isset($c)) { return ''; }

	if(isset($c['w'])) { $cw = $c['w']; } else { $cw = -1; }
	if(isset($c['w0'])) { $cw0 = $c['w0']; } else { $cw0 = -1; }
	if(isset($c['h0'])) { $ch0 = $c['h0']; } else { $ch0 = -1; }

	$data = pack("n2d19A1A1n2sA32A128",
		$c['colspan'],		/* n  (16 bit; 2 bytes) [pos 0] */
		$c['rowspan'],		/* n (16 bit; 2 bytes) [pos 2] */
		$c['s'],			/* d NB machine-dependent size (64 bit; 8 bytes on test winOS) [pos 4] */
		$c['maxs'],			/* d NB machine-dependent size [pos 4 + 1*D] */
		$c['nestedmaw'],		/* d NB machine-dependent size [pos 4 + 2*D] */
		$c['nestedmiw'],		/* d NB machine-dependent size [pos 4 + 3*D] */
		$c['padding']['L'],	/* d NB machine-dependent size [pos 4 + 4*D] */
		$c['padding']['R'],	/* d NB machine-dependent size [pos 4 + 5*D] */
		$c['padding']['T'],	/* d NB machine-dependent size [pos 4 + 6*D] */
		$c['padding']['B'],	/* d NB machine-dependent size [pos 4 + 7*D] */
		$c['dfs'],			/* d NB machine-dependent size [pos 4 + 8*D] */
		$cw,				/* d NB machine-dependent size [pos 4 + 9*D] */
		$c['h'],			/* d NB machine-dependent size [pos 4 + 10*D] */
		$c['absmiw'],		/* d NB machine-dependent size [pos 4 + 11*D] */
		$c['maw'],			/* d NB machine-dependent size [pos 4 + 12*D] */
		$c['miw'],			/* d NB machine-dependent size [pos 4 + 13*D] */
		$c['mih'],			/* d NB machine-dependent size [pos 4 + 14*D] */
		$c['x0'],			/* d NB machine-dependent size [pos 4 + 15*D] */
		$cw0,				/* d NB machine-dependent size [pos 4 + 16*D] */
		$c['y0'],			/* d NB machine-dependent size [pos 4 + 17*D] */
		$ch0,				/* d NB machine-dependent size [pos 4 + 18*D] */
		$c['a'],			/* A1 (1 byte) [pos 4 + 19*D] */
		$c['va'],			/* A1 (1 byte) [pos 5 + 19*D] */
		$c['nowrap'],		/* 1 or blank  n (16 bit; 2 bytes) [pos 6 + 19*D] */
		$c['wpercent'],		/* 0 - 100  n (16 bit; 2 bytes) [pos 8 + 19*D] */
		$c['R'],			/* 90 or -90 s (16 bit; 2 bytes) [pos 10 + 19*D] */
		$c['bgcolor'],		/* A32 (32 bytes) [pos 12 + 19*D] */
		$c['gradient']		/* A128 (128 bytes) [pos 44 + 19*D] */
	);

	if ($c['background-image']) {
		$data .= pack("n2d2A6A6n3dA4A128",
		strlen($data),		/* offset in main data to start of bgimage data */
		$c['background-image']['image_id'],	/* n */
		$c['background-image']['orig_w'],	/* d NB machine-dependent size */
		$c['background-image']['orig_h'],	/* d NB machine-dependent size */
		$c['background-image']['x_pos'],	/* A6 calc size or "50%" */
		$c['background-image']['y_pos'],	/* A6 calc size or "50%" */
		$c['background-image']['x_repeat'],	/* n true or false*/
		$c['background-image']['y_repeat'],	/* n true or false */
		$c['background-image']['resize'],	/* n 0 - 6 */
		$c['background-image']['opacity'],	/* d  0-1 */
		$c['background-image']['itype'],	/* A4 jpg etc */
		$c['background-image']['gradient']	/* A128 CSS string */
		);
	}
	else $data .= pack("n",0);
	$tb_offset = 2 + 186 + 2 + strlen($data);
	$stb = serialize($c['textbuffer']).'        ';	// buffer to allow updating in reverseTableDir
	$lentb = strlen($stb);
	$data2 = pack("nA".$lentb, $lentb, $stb);

	$tempfh = true;
	if ($mode=="W" && $offset) {
		if (!$fh) { $fh = fopen($file, "r+b"); }	// Overwrite (but not truncate)
		else $tempfh = false;
		fseek($fh, $offset);
	}
	else {
		$fh = fopen($file, "ab");	// APPEND
	}
	fwrite($fh, pack("n",$tb_offset));		// Offset to Text buffer 2 bytes
	fwrite($fh, $c['borderbin']);			// border details 186 bytes
	fwrite($fh, pack("n",strlen($data)));	// Length of Main data
	fwrite($fh, $data);				// Main data
	fwrite($fh, $data2);				// Text buffer (starts with "n" length of text buffer)
	if ($tempfh) fclose($fh);
	return ($tb_offset + 2 + $lentb);
}

function _cacheUpdateTxB($c, $fh, $offset) {
	fseek($fh, $offset);
	$tb_offset = $this->read_short($fh);		// First entry = Offset to Text buffer  2 bytes
	fseek($fh, ($tb_offset-2), SEEK_CUR);
	$lentb = $this->read_short($fh);			// First entry in textbuffer = Length of serialized textbuffer - do not update
	$stb = serialize($c['textbuffer']);
	fwrite($fh, pack("A".$lentb, $stb));
}

function _cacheUpdateBorder($c, $fh, $offset) {
	$offset += 2;
	fseek($fh, $offset);
	fwrite($fh, $c['borderbin']);
}

function _cacheUpdateMtx($c, $fh, $offset, $var) {
	if ($var=='mih') { $offset += (2 + 186 + 2 + 4 + 14*_DSIZE); }
	else if ($var=='x0') { $offset += (2 + 186 + 2 + 4 + 15*_DSIZE); }	// x0 and w0
	else if ($var=='y0') { $offset += (2 + 186 + 2 + 4 + 17*_DSIZE); }	// y0 and h0
	fseek($fh, $offset);
	if ($var=='mih') { fwrite($fh, pack("d",$c['mih'])); }
	else if ($var=='x0') { fwrite($fh, pack("d2",$c['x0'],$c['w0'])); }
	else if ($var=='y0') { fwrite($fh, pack("d2",$c['y0'],$c['h0'])); }
}

function _uncacheCell($ptr, $file, $fh) {
	// Requires either $file or $fh (file_handle)
	if ($ptr==0) { return null; }
	if (is_array($ptr)) { $this->Error("Probable cause - missing end tag &lt;/td&gt;. You may be able to change the configurable variable: allow_html_optional_endtags "); }
	$tempfh = true;
	if (!$fh) { $fh = fopen($file, "rb"); }
	else $tempfh = false;
	fseek($fh, $ptr);
	$c = array();
	$tb_offset = $this->read_short($fh);		// First entry = Offset to Text buffer  2 bytes
	$c['borderbin'] = fread($fh,186);			// border details 186 bytes
	$maindatalen = $this->read_short($fh);		// Length of Main data (2 bytes)
	$str = fread($fh,$maindatalen );			// Main data
	$data = unpack("ncolspan/nrowspan/ds/dmaxs/dnmaw/dnmiw/dpl/dpr/dpt/dpb/ddfs/dw/dh/dabsmiw/dmaw/dmiw/dmih/dx0/dw0/dy0/dh0/A1a/A1va/nnowrap/nwpercent/sR/A32bgcol/A128grad/nbgimage", $str);

		if ($data['colspan']>0) $c['colspan'] = $data['colspan'];		/* n */
		if ($data['rowspan']>0) $c['rowspan'] = $data['rowspan'];		/* n */
		$c['s'] = $data['s'];				/* d NB machine-dependent size */
		$c['maxs'] = $data['maxs'];			/* d NB machine-dependent size */
		if ($data['nmaw']>0) $c['nestedmaw'] = $data['nmaw'];		/* d NB machine-dependent size */
		if ($data['nmiw']>0) $c['nestedmiw'] = $data['nmiw'];		/* d NB machine-dependent size */
		$c['padding']['L'] = $data['pl'];		/* d NB machine-dependent size */
		$c['padding']['R'] = $data['pr'];		/* d NB machine-dependent size */
		$c['padding']['T'] = $data['pt'];		/* d NB machine-dependent size */
		$c['padding']['B'] = $data['pb'];		/* d NB machine-dependent size */
		$c['dfs'] = $data['dfs'];			/* d NB machine-dependent size */
		if ($data['w']>=0) $c['w'] = $data['w'];	/* d NB machine-dependent size */
		if ($data['h']>0) $c['h'] = $data['h'];	/* d NB machine-dependent size */
		if ($data['absmiw']>0) $c['absmiw'] = $data['absmiw'];	/* d NB machine-dependent size */
		if ($data['maw']>0) $c['maw'] = $data['maw'];	/* d NB machine-dependent size */
		if ($data['miw']>0) $c['miw'] = $data['miw'];	/* d NB machine-dependent size */
		if ($data['mih']>0) $c['mih'] = $data['mih'];	/* d NB machine-dependent size */
		if ($data['w0']>=0) { $c['w0'] = $data['w0'];	/* d NB machine-dependent size */
			$c['x0'] = $data['x0']; }	/* d NB machine-dependent size */
		if ($data['h0']>=0) { $c['h0'] = $data['h0'];	/* d NB machine-dependent size */
			$c['y0'] = $data['y0']; }	/* d NB machine-dependent size */
		$c['a'] = trim($data['a']);			/* A1 */
		$c['va'] = trim($data['va']);			/* A1 */
		if ($data['nowrap']) $c['nowrap'] = $data['nowrap'];		/* 1 or blank  n */
			else $c['nowrap'] = false;
		if ($data['wpercent']>0) $c['wpercent'] = $data['wpercent'];	/* 0 - 100  n */
		if ($data['R']<>0) $c['R'] = $data['R'];				/* 90 or -90 s */
			else $c['R'] = false;
		$c['bgcolor'] = trim($data['bgcol']);			/* A32 */
		if (trim($data['grad']))
			$c['gradient'] = trim($data['grad']);		/* A128 */
		  else $c['gradient'] = false;
		if ($data['bgimage']>0) {
			$bgidata = substr($str, ($data['bgimage']+2));
			$c['background-image'] = unpack("nimage_id/dorig_w/dorig_h/A6x_pos/A6y_pos/nx_repeat/ny_repeat/nresize/dopacity/A4itype/A128gradient", $bgidata);
		}

	$tblen = $this->read_short($fh);				// Length of Textbuffer
	$tbsp = fread($fh,$tblen);					// Textbuffer (serialised and packed)
	$tbs = unpack("A".$tblen."textbuffer",$tbsp);		// Textbuffer unpacked
	$c['textbuffer'] = unserialize(trim($tbs['textbuffer']));	// Textbuffer unserialized

	if ($tempfh) { fclose($fh); }
	return ($c);
}
function read_short(&$fh) {
		$s = fread($fh,2);
		$a = (ord($s[0])<<8) + ord($s[1]);
		if ($a & (1 << 15) ) {
			$a = ($a - (1 << 16));
		}
		return $a;
}
function _backupCacheFiles() {
	foreach($this->table AS $lvl=>$t) {
		foreach($this->table[$lvl] AS $c=>$t2) {
///////////////////////////if (!file_exists($t2['cache'])) { echo $lvl; echo $c; print_r($this->table); exit; }
			copy( $t2['cache'], $t2['cache'].'.bak');
		}
	}
}
function _restoreCacheFiles() {
	foreach($this->table AS $lvl=>$t) {
		foreach($this->table[$lvl] AS $c=>$t2) {
			copy( $t2['cache'].'.bak', $t2['cache']);
		}
	}
}


function _packCellBorder($cell) {
	if (!is_array($cell) || !isset($cell)) { return ''; }

	if (!$this->packTableData) { return $cell; }
	// = 186 bytes
	$bindata = pack("nnda6A10nnda6A10nnda6A10nnda6A10nd9",
	$cell['border'],
	$cell['border_details']['R']['s'],
	$cell['border_details']['R']['w'],
	$cell['border_details']['R']['c'],
	$cell['border_details']['R']['style'],
	$cell['border_details']['R']['dom'],

	$cell['border_details']['L']['s'],
	$cell['border_details']['L']['w'],
	$cell['border_details']['L']['c'],
	$cell['border_details']['L']['style'],
	$cell['border_details']['L']['dom'],

	$cell['border_details']['T']['s'],
	$cell['border_details']['T']['w'],
	$cell['border_details']['T']['c'],
	$cell['border_details']['T']['style'],
	$cell['border_details']['T']['dom'],

	$cell['border_details']['B']['s'],
	$cell['border_details']['B']['w'],
	$cell['border_details']['B']['c'],
	$cell['border_details']['B']['style'],
	$cell['border_details']['B']['dom'],

	$cell['border_details']['mbw']['BL'],
	$cell['border_details']['mbw']['BR'],
	$cell['border_details']['mbw']['RT'],
	$cell['border_details']['mbw']['RB'],
	$cell['border_details']['mbw']['TL'],
	$cell['border_details']['mbw']['TR'],
	$cell['border_details']['mbw']['LT'],
	$cell['border_details']['mbw']['LB'],

	$cell['border_details']['cellposdom']
	);
	return $bindata;
}



function _getBorderWidths($bindata) {
	if (!$bindata) { return array(0,0,0,0); }
	if (!$this->packTableData) { return array($bindata['border_details']['T']['w'], $bindata['border_details']['R']['w'], $bindata['border_details']['B']['w'], $bindata['border_details']['L']['w']); }

	$bd = unpack("nbord/nrs/drw/a6rca/A10rst/nrd/nls/dlw/a6lca/A10lst/nld/nts/dtw/a6tca/A10tst/ntd/nbs/dbw/a6bca/A10bst/nbd/dmbl/dmbr/dmrt/dmrb/dmtl/dmtr/dmlt/dmlb/dcpd", $bindata);
	$cell['border_details']['R']['w'] = $bd['rw'];
	$cell['border_details']['L']['w'] = $bd['lw'];
	$cell['border_details']['T']['w'] = $bd['tw'];
	$cell['border_details']['B']['w'] = $bd['bw'];
	return array($bd['tw'], $bd['rw'], $bd['bw'], $bd['lw']);
}


function _unpackCellBorder($bindata) {
	if (!$bindata) { return array(); }
	if (!$this->packTableData) { return $bindata; }

	$bd = unpack("nbord/nrs/drw/a6rca/A10rst/nrd/nls/dlw/a6lca/A10lst/nld/nts/dtw/a6tca/A10tst/ntd/nbs/dbw/a6bca/A10bst/nbd/dmbl/dmbr/dmrt/dmrb/dmtl/dmtr/dmlt/dmlb/dcpd", $bindata);

	$cell['border'] = $bd['bord'];
	$cell['border_details']['R']['s'] = $bd['rs'];
	$cell['border_details']['R']['w'] = $bd['rw'];
	$cell['border_details']['R']['c'] = $bd['rca'];
	$cell['border_details']['R']['style'] = trim($bd['rst']);
	$cell['border_details']['R']['dom'] = $bd['rd'];

	$cell['border_details']['L']['s'] = $bd['ls'];
	$cell['border_details']['L']['w'] = $bd['lw'];
	$cell['border_details']['L']['c'] = $bd['lca'];
	$cell['border_details']['L']['style'] = trim($bd['lst']);
	$cell['border_details']['L']['dom'] = $bd['ld'];

	$cell['border_details']['T']['s'] = $bd['ts'];
	$cell['border_details']['T']['w'] = $bd['tw'];
	$cell['border_details']['T']['c'] = $bd['tca'];
	$cell['border_details']['T']['style'] = trim($bd['tst']);
	$cell['border_details']['T']['dom'] = $bd['td'];

	$cell['border_details']['B']['s'] = $bd['bs'];
	$cell['border_details']['B']['w'] = $bd['bw'];
	$cell['border_details']['B']['c'] = $bd['bca'];
	$cell['border_details']['B']['style'] = trim($bd['bst']);
	$cell['border_details']['B']['dom'] = $bd['bd'];

	$cell['border_details']['mbw']['BL'] = $bd['mbl'];
	$cell['border_details']['mbw']['BR'] = $bd['mbr'];
	$cell['border_details']['mbw']['RT'] = $bd['mrt'];
	$cell['border_details']['mbw']['RB'] = $bd['mrb'];
	$cell['border_details']['mbw']['TL'] = $bd['mtl'];
	$cell['border_details']['mbw']['TR'] = $bd['mtr'];
	$cell['border_details']['mbw']['LT'] = $bd['mlt'];
	$cell['border_details']['mbw']['LB'] = $bd['mlb'];
	$cell['border_details']['cellposdom'] = $bd['cpd'];

	return($cell);
}


////////////////////////TABLE CODE (from PDFTable)/////////////////////////////////////
////////////////////////TABLE CODE (from PDFTable)/////////////////////////////////////
////////////////////////TABLE CODE (from PDFTable)/////////////////////////////////////
//table		Array of (w, h, bc, nr, wc, hr, cells)
//w			Width of table
//h			Height of table
//nc			Number column
//nr			Number row
//hr			List of height of each row
//wc			List of width of each column
//cells		List of cells of each rows, cells[i][j] is a cell in the table
function _tableColumnWidth(&$table,$firstpass=false){
	$cs = &$table['cells'];

	$nc = $table['nc'];
	$nr = $table['nr'];
	$listspan = array();

	if ($table['borders_separate']) {
		$tblbw = $table['border_details']['L']['w'] + $table['border_details']['R']['w'] + $table['margin']['L'] + $table['margin']['R'] +  $table['padding']['L'] + $table['padding']['R'] + $table['border_spacing_H'];
	}
	else { $tblbw = $table['max_cell_border_width']['L']/2 + $table['max_cell_border_width']['R']/2 + $table['margin']['L'] + $table['margin']['R']; }

	if ($this->cacheTables) { $fh = fopen($table['cache'], "r+b"); }
	else { $fh = null; }

	// ADDED table['l'][colno]
	// = total length of text approx (using $c['s']) in that column - used to approximately distribute col widths in _tableWidth
	//
	for($j = 0 ; $j < $nc ; $j++ ) { //columns
		$wc = &$table['wc'][$j];
		for($i = 0 ; $i < $nr ; $i++ ) { //rows
			if (isset($cs[$i][$j]) && $cs[$i][$j])  {
				if ($this->cacheTables) {
					$c = $this->_uncacheCell($table['cells'][$i][$j], '', $fh);
				}
				else
					$c = &$cs[$i][$j];

				if ($this->simpleTables){
					   if ($table['borders_separate']) {	// NB twice border width
						$extrcw = $table['simple']['border_details']['L']['w'] + $table['simple']['border_details']['R']['w'] + $c['padding']['L'] + $c['padding']['R'] + $table['border_spacing_H'];
					   }
					   else {
						$extrcw = $table['simple']['border_details']['L']['w']/2 + $table['simple']['border_details']['R']['w']/2 + $c['padding']['L'] + $c['padding']['R'];
					   }
				}
				else {
			 	   if ($this->packTableData) {
			 	   	list($bt,$br,$bb,$bl) = $this->_getBorderWidths($c['borderbin']);
			 	   }
			 	   else {
					$br = $c['border_details']['R']['w'];
					$bl = $c['border_details']['L']['w'];
				   }
				   if ($table['borders_separate']) {	// NB twice border width
					$extrcw = $bl + $br + $c['padding']['L'] + $c['padding']['R'] + $table['border_spacing_H'];
				   }
				   else {
					$extrcw = $bl/2 + $br/2 + $c['padding']['L'] + $c['padding']['R'];
				   }
				}

				//$mw = $this->GetStringWidth('W') + $extrcw ;
				$mw = 0;

				$c['absmiw'] = $mw;

				if (isset($c['R']) && $c['R']) {
					$c['maw'] = $c['miw'] = $this->FontSize + $extrcw ;
					if (isset($c['w'])) {	// If cell width is specified
						if ($c['miw'] <$c['w'])	{ $c['miw'] = $c['w']; }
					}
					if (!isset($c['colspan'])) {
						if ($wc['miw'] < $c['miw']) { $wc['miw']	= $c['miw']; }
						if ($wc['maw'] < $c['maw']) { $wc['maw']	= $c['maw']; }

						if ($firstpass) {
						   if (isset($table['l'][$j]) ) {
							$table['l'][$j] += $c['miw'] ;
						   }
						   else {
							$table['l'][$j] = $c['miw'] ;
						   }
						}
					}
					if ($c['miw'] > $wc['miw']) { $wc['miw'] = $c['miw']; }
        				if ($wc['miw'] > $wc['maw']) { $wc['maw'] = $wc['miw']; }
					continue;
				}

				if ($firstpass) {
					if (isset($c['s'])) { $c['s'] += $extrcw; }
					if (isset($c['maxs'])) { $c['maxs'] += $extrcw; }
					if (isset($c['nestedmiw'])) { $c['nestedmiw'] += $extrcw; }
					if (isset($c['nestedmaw'])) { $c['nestedmaw'] += $extrcw; }
				}


				// If minimum width has already been set by a nested table or inline object (image/form), use it
				if (isset($c['nestedmiw']) && $this->table[1][1]['overflow']!='visible') { $miw = $c['nestedmiw']; }
				else  { $miw = $mw; }

				if (isset($c['maxs']) && $c['maxs'] != '') { $c['s'] = $c['maxs']; }

				// If maximum width has already been set by a nested table, use it
				if (isset($c['nestedmaw'])) { $c['maw'] = $c['nestedmaw']; }
				else $c['maw'] = $c['s'];

				if (isset($table['overflow']) && $table['overflow']=='visible' && $table['level']==1) {
					if (($c['maw'] + $tblbw) > $this->blk[$this->blklvl]['inner_width']) {
						$c['maw'] = $this->blk[$this->blklvl]['inner_width'] - $tblbw;
					}
				}

				if (isset($c['nowrap']) && $c['nowrap']) { $miw = $c['maw']; }

				if (isset($c['wpercent']) && $firstpass) {
	 				if (isset($c['colspan'])) {	// Not perfect - but % set on colspan is shared equally on cols.
					   for($k=0;$k<$c['colspan'];$k++) {
						$table['wc'][($j+$k)]['wpercent'] = $c['wpercent'] / $c['colspan'];
					   }
					}
	 				else {
						if (isset($table['w']) && $table['w']) { $c['w'] = $c['wpercent']/100 * ($table['w'] - $tblbw ); }
						$wc['wpercent'] = $c['wpercent'];
					}
				}

				if (isset($table['overflow']) && $table['overflow']=='visible' && $table['level']==1) {
					if (($c['w'] + $tblbw) > $this->blk[$this->blklvl]['inner_width']) {
						$c['w'] = $this->blk[$this->blklvl]['inner_width'] - $tblbw;
					}
				}


				if (isset($c['w'])) {	// If cell width is specified
					if ($miw<$c['w'])	{ $c['miw'] = $c['w']; }	// Cell min width = that specified
					if ($miw>$c['w'])	{ $c['miw'] = $c['w'] = $miw; } // If width specified is less than minimum allowed (W) increase it
					if (!isset($wc['w'])) { $wc['w'] = 1; }		// If the Col width is not specified = set it to 1

				}
				else { $c['miw'] = $miw; }	// If cell width not specified -> set Cell min width it to minimum allowed (W)

				if ($c['maw']  < $c['miw']) { $c['maw'] = $c['miw']; }	// If Cell max width < Minwidth - increase it to =
				if (!isset($c['colspan'])) {
					if ($wc['miw'] < $c['miw']) { $wc['miw']	= $c['miw']; }	// Update Col Minimum and maximum widths
					if ($wc['maw'] < $c['maw']) { $wc['maw']	= $c['maw']; }
					if ((isset($wc['absmiw']) && $wc['absmiw'] < $c['absmiw']) || !isset($wc['absmiw'])) { $wc['absmiw'] = $c['absmiw']; }	// Update Col Minimum and maximum widths

					if (isset($table['l'][$j]) ) {
						$table['l'][$j] += $c['s'];
					}
					else {
						$table['l'][$j] = $c['s'];
					}

				}
				else {
					$listspan[] = array($i,$j);
				}

				//Check if minimum width of the whole column is big enough for largest word to fit
				if (isset($c['textbuffer']) && (!isset($table['overflow']) || $table['overflow']!='wrap')) {	// mPDF 5.5.11
		       			$minwidth = $this->TableCheckMinWidth($wc['miw']- $extrcw ,0,$c['textbuffer']);
				}
				else { $minwidth = 0; }
        			if ($minwidth < 0) {
					//increase minimum width
					if (!isset($c['colspan'])) {
						$wc['miw'] = max($wc['miw'],((-$minwidth) + $extrcw) );
					}
					else {
						$c['miw'] = max($c['miw'],((-$minwidth) + $extrcw) );
					}
				}
 				if (!isset($c['colspan'])) {
	        			if ($wc['miw'] > $wc['maw']) { $wc['maw'] = $wc['miw']; } //update maximum width, if needed
				}
				if ($this->cacheTables) {
					$this->_cacheCell($c, '', "W", $fh, $table['cells'][$i][$j]);
				}
			}
			unset($c);
		}//rows
	}//columns


	// COLUMN SPANS
	$wc = &$table['wc'];
	foreach ($listspan as $span) {
		list($i,$j) = $span;
		if ($this->cacheTables) {
			$c = $this->_uncacheCell($cs[$i][$j], '', $fh);
		}
		else
			$c = &$cs[$i][$j];
		$lc = $j + $c['colspan'];
		if ($lc > $nc) { $lc = $nc; }
		$wis = $wisa = 0;
		$was = $wasa = 0;
		$list = array();
		for($k=$j;$k<$lc;$k++) {
			if (isset($table['l'][$k]) ) {
				if ($c['R']) { $table['l'][$k] += $c['miw']/$c['colspan'] ; }
				else { $table['l'][$k] += $c['s']/$c['colspan']; }
			}
			else {
				if ($c['R']) { $table['l'][$k] = $c['miw']/$c['colspan'] ; }
				else { $table['l'][$k] = $c['s']/$c['colspan']; }
			}
			$wis += $wc[$k]['miw'];
			$was += $wc[$k]['maw'];
			if (!isset($c['w'])) {
				$list[] = $k;
				$wisa += $wc[$k]['miw'];
				$wasa += $wc[$k]['maw'];
			}
		}
		if ($c['miw'] > $wis) {
			if (!$wis) {
				for($k=$j;$k<$lc;$k++) { $wc[$k]['miw'] = $c['miw']/$c['colspan']; }
			}
			else if (!count($list)) {
				$wi = $c['miw'] - $wis;
				for($k=$j;$k<$lc;$k++) { $wc[$k]['miw'] += ($wc[$k]['miw']/$wis)*$wi; }
			}
			else {
				$wi = $c['miw'] - $wis;
				foreach ($list as $k) { $wc[$k]['miw'] += ($wc[$k]['miw']/$wisa)*$wi; }
			}
		}
		if ($c['maw'] > $was) {
			if (!$wis) {
				for($k=$j;$k<$lc;$k++) { $wc[$k]['maw'] = $c['maw']/$c['colspan']; }
			}
			else if (!count($list)) {
				$wi = $c['maw'] - $was;
				for($k=$j;$k<$lc;$k++) { $wc[$k]['maw'] += ($wc[$k]['maw']/$was)*$wi; }
			}
			else {
				$wi = $c['maw'] - $was;
				foreach ($list as $k) { $wc[$k]['maw'] += ($wc[$k]['maw']/$wasa)*$wi; }
			}
		}
		unset($c);
	}

	if ($this->cacheTables) { fclose($fh); }

	$checkminwidth = 0;
	$checkmaxwidth = 0;
	$totallength = 0;

	for( $i = 0 ; $i < $nc ; $i++ ) {
		$checkminwidth += $table['wc'][$i]['miw'];
		$checkmaxwidth += $table['wc'][$i]['maw'];
		$totallength += $table['l'][$i];
	}

	if (!isset($table['w']) && $firstpass) {
	   $sumpc = 0;
	   $notset = 0;
	   for( $i = 0 ; $i < $nc ; $i++ ) {
		  if (isset($table['wc'][$i]['wpercent']) && $table['wc'][$i]['wpercent']) {
			$sumpc += $table['wc'][$i]['wpercent'];
		  }
		  else { $notset++; }
	   }

	   // If sum of widths as %  >= 100% and not all columns are set
		// Set a nominal width of 1% for unset columns
	   if ($sumpc >= 100 && $notset) {
	   	for( $i = 0 ; $i < $nc ; $i++ ) {
		  if ((!isset($table['wc'][$i]['wpercent']) || !$table['wc'][$i]['wpercent']) &&
			(!isset($table['wc'][$i]['w']) || !$table['wc'][$i]['w'])) {
			$table['wc'][$i]['wpercent'] = 1;
		  }
	   	}
	   }


	   if ($sumpc) {	// if any percents are set
		$sumnonpc = (100 - $sumpc);
		$sumpc = max($sumpc,100);
	      $miwleft = 0;
		$miwleftcount = 0;
		$miwsurplusnonpc = 0;
		$maxcalcmiw  = 0;
	      $mawleft = 0;
		$mawleftcount = 0;
		$mawsurplusnonpc = 0;
		$maxcalcmaw  = 0;
		for( $i = 0 ; $i < $nc ; $i++ ) {
		  if (isset($table['wc'][$i]['wpercent'])) {
			$maxcalcmiw = max($maxcalcmiw, ($table['wc'][$i]['miw'] * $sumpc /$table['wc'][$i]['wpercent']) );
			$maxcalcmaw = max($maxcalcmaw, ($table['wc'][$i]['maw'] * $sumpc /$table['wc'][$i]['wpercent']) );
		  }
		  else {
			$miwleft += $table['wc'][$i]['miw'];
			$mawleft += $table['wc'][$i]['maw'];
		  	if (!isset($table['wc'][$i]['w'])) { $miwleftcount++; $mawleftcount++; }
		  }
		}
		if ($miwleft && $sumnonpc > 0) { $miwnon = $miwleft * 100 / $sumnonpc; }
		if ($mawleft && $sumnonpc > 0) { $mawnon = $mawleft * 100 / $sumnonpc; }
		if (($miwnon > $checkminwidth || $maxcalcmiw > $checkminwidth) && $this->keep_table_proportions) {
			if ($miwnon > $maxcalcmiw) {
				$miwsurplusnonpc = round((($miwnon * $sumnonpc / 100) - $miwleft),3);
				$checkminwidth = $miwnon;
			}
			else { $checkminwidth = $maxcalcmiw; }
			for( $i = 0 ; $i < $nc ; $i++ ) {
			  if (isset($table['wc'][$i]['wpercent'])) {
				$newmiw = $checkminwidth * $table['wc'][$i]['wpercent']/100;
				if ($table['wc'][$i]['miw'] < $newmiw) {
				  $table['wc'][$i]['miw'] = $newmiw;
				}
				$table['wc'][$i]['w'] = 1;
			  }
			  else if ($miwsurplusnonpc && !$table['wc'][$i]['w']) {
				$table['wc'][$i]['miw'] +=  $miwsurplusnonpc / $miwleftcount;
			  }
			}
		}
		if (($mawnon > $checkmaxwidth || $maxcalcmaw > $checkmaxwidth )) {
			if ($mawnon > $maxcalcmaw) {
				$mawsurplusnonpc = round((($mawnon * $sumnonpc / 100) - $mawleft),3);
				$checkmaxwidth = $mawnon;
			}
			else { $checkmaxwidth = $maxcalcmaw; }
			for( $i = 0 ; $i < $nc ; $i++ ) {
			  if (isset($table['wc'][$i]['wpercent'])) {
				$newmaw = $checkmaxwidth * $table['wc'][$i]['wpercent']/100;
				if ($table['wc'][$i]['maw'] < $newmaw) {
				  $table['wc'][$i]['maw'] = $newmaw;
				}
				$table['wc'][$i]['w'] = 1;
			  }
			  else if ($mawsurplusnonpc && !$table['wc'][$i]['w']) {
				$table['wc'][$i]['maw'] +=  $mawsurplusnonpc / $mawleftcount;
			  }
			  if ($table['wc'][$i]['maw'] < $table['wc'][$i]['miw']) { $table['wc'][$i]['maw'] = $table['wc'][$i]['miw']; }
			}
		}
		if ($checkminwidth > $checkmaxwidth) { $checkmaxwidth = $checkminwidth; }
	   }
	}

	if (isset($table['wpercent']) && $table['wpercent']) {
		$checkminwidth *= (100 / $table['wpercent']);
		$checkmaxwidth *= (100 / $table['wpercent']);
	}


	$checkminwidth += $tblbw ;
	$checkmaxwidth += $tblbw ;

	// Table['miw'] set by percent in first pass may be larger than sum of column miw
	if ((isset($table['miw']) && $checkminwidth > $table['miw']) || !isset($table['miw'])) {  $table['miw'] = $checkminwidth; }
	if ((isset($table['maw']) && $checkmaxwidth > $table['maw']) || !isset($table['maw'])) { $table['maw'] = $checkmaxwidth; }
	$table['tl'] = $totallength ;


	if (!$this->tableCJK) {
		if ($this->table_rotate) {
			$mxw = $this->tbrot_maxw;
		}
		else {
			$mxw = $this->blk[$this->blklvl]['inner_width'];
		}
		if(!isset($table['overflow'])) { $table['overflow'] = null; }
		if ($table['overflow']=='visible') {
			return array(0,0);
		}
		else if ($table['overflow']=='hidden' && !$this->table_rotate && !$this->ColActive && $checkminwidth > $mxw) {
			$table['w'] = $table['miw'];
			return array(0,0);
		}
		else if ($table['overflow']=='wrap') { return array(0,0); }

		if (isset($table['w']) && $table['w'] ) {
			if ($table['w'] >= $checkminwidth && $table['w'] <= $mxw) { $table['maw'] = $mxw = $table['w']; }
			else if ($table['w'] >= $checkminwidth && $table['w'] > $mxw && $this->keep_table_proportions) { $checkminwidth = $table['w']; }
			else {
				unset($table['w']);
			}
		}
		$ratio = $checkminwidth/$mxw;
		if ($checkminwidth > $mxw) { return array(($ratio +0.001),$checkminwidth); }	// 0.001 to allow for rounded numbers when resizing
	}
	unset($cs);
	return array(0,0);
}



function _tableWidth(&$table){
	$widthcols = &$table['wc'];
	$numcols = $table['nc'];
	$tablewidth = 0;
	if ($table['borders_separate']) {
		$tblbw = $table['border_details']['L']['w'] + $table['border_details']['R']['w'] + $table['margin']['L'] + $table['margin']['R'] +  $table['padding']['L'] + $table['padding']['R'] + $table['border_spacing_H'];
	}
	else { $tblbw = $table['max_cell_border_width']['L']/2 + $table['max_cell_border_width']['R']/2 + $table['margin']['L'] + $table['margin']['R']; }

	if ($table['level']>1 && isset($table['w'])) {
		if (isset($table['wpercent']) && $table['wpercent']) {
			$table['w'] = $temppgwidth = (($table['w']-$tblbw) * $table['wpercent'] / 100) + $tblbw ;
		}
		else {
			$temppgwidth = $table['w'] ;
		}
	}
	else if ($this->table_rotate) {
		$temppgwidth = $this->tbrot_maxw;
		// If it is less than 1/20th of the remaining page height to finish the DIV (i.e. DIV padding + table bottom margin)
		// then allow for this
		$enddiv = $this->blk[$this->blklvl]['padding_bottom'] + $this->blk[$this->blklvl]['border_bottom']['w'];
		if ($enddiv/$temppgwidth <0.05) { $temppgwidth -= $enddiv; }
	}
	else {
		if (isset($table['w']) && $table['w']< $this->blk[$this->blklvl]['inner_width']) {
			$notfullwidth = 1;
			$temppgwidth = $table['w'] ;
		}
		else if ($table['overflow']=='visible' && $table['level'] ==1) {
			$temppgwidth = null;
		}
		else if ($table['overflow']=='hidden' && !$this->ColActive && isset($table['w']) && $table['w'] > $this->blk[$this->blklvl]['inner_width'] && $table['w']==$table['miw']) {
			//$temppgwidth = $this->blk[$this->blklvl]['inner_width'];
			$temppgwidth = $table['w'] ;
		}
		else { $temppgwidth = $this->blk[$this->blklvl]['inner_width']; }
	}


	$totaltextlength = 0;	// Added - to sum $table['l'][colno]
	$totalatextlength = 0;	// Added - to sum $table['l'][colno] for those columns where width not set
	$percentages_set = 0;
	for ( $i = 0 ; $i < $numcols ; $i++ ) {
		if (isset($widthcols[$i]['wpercent']))  { $tablewidth += $widthcols[$i]['maw']; $percentages_set = 1; }
		else if (isset($widthcols[$i]['w']))  { $tablewidth += $widthcols[$i]['miw']; }
		else { $tablewidth += $widthcols[$i]['maw']; }
		$totaltextlength += $table['l'][$i];
	}
	if (!$totaltextlength) { $totaltextlength =1; }
	$tablewidth += $tblbw;	// Outer half of table borders

	if ($tablewidth > $temppgwidth) {
		$table['w'] = $temppgwidth;
	}
	// if any widths set as percentages and max width fits < page width
	else if ($tablewidth < $temppgwidth && !isset($table['w']) && $percentages_set) {
		$table['w'] = $table['maw'];
	}
	// if table width is set and is > allowed width
	if (isset($table['w']) && $table['w'] > $temppgwidth) { $table['w'] = $temppgwidth; }
	// IF the table width is now set - Need to distribute columns widths
	if (isset($table['w'])) {
		$wis = $wisa = 0;
		$list = array();
		$notsetlist = array();
		for( $i = 0 ; $i < $numcols ; $i++ ) {
			$wis += $widthcols[$i]['miw'];
			if (!isset($widthcols[$i]['w']) || ($widthcols[$i]['w'] && $table['w'] > $temppgwidth && !$this->keep_table_proportions && !$notfullwidth )){
				$list[] = $i;
				$wisa += $widthcols[$i]['miw'];
				$totalatextlength += $table['l'][$i];
			}
		}
		if (!$totalatextlength) { $totalatextlength =1; }

		// Allocate spare (more than col's minimum width) across the cols according to their approx total text length
		// Do it by setting minimum width here
		if ($table['w'] > $wis + $tblbw) {
			// First set any cell widths set as percentages
			if ($table['w'] < $temppgwidth || $this->keep_table_proportions) {
				for($k=0;$k<$numcols;$k++) {
					if (isset($widthcols[$k]['wpercent'])) {
						$curr = $widthcols[$k]['miw'];
						$widthcols[$k]['miw'] = ($table['w']-$tblbw) * $widthcols[$k]['wpercent']/100;
						$wis += $widthcols[$k]['miw'] - $curr;
						$wisa += $widthcols[$k]['miw'] - $curr;
					}
				}
			}
			// Now allocate surplus up to maximum width of each column
			$surplus = 0;  $ttl = 0;	// number of surplus columns
			if (!count($list)) {
				$wi = ($table['w']-($wis + $tblbw));	//	i.e. extra space to distribute
				for($k=0;$k<$numcols;$k++) {
					$spareratio = ($table['l'][$k] / $totaltextlength); //  gives ratio to divide up free space
					// Don't allocate more than Maximum required width - save rest in surplus
					if ($widthcols[$k]['miw'] + ($wi * $spareratio) > $widthcols[$k]['maw']) {
						$surplus += ($wi * $spareratio) - ($widthcols[$k]['maw']-$widthcols[$k]['miw']);
						$widthcols[$k]['miw'] = $widthcols[$k]['maw'];
					}
					else {
						$notsetlist[] = $k;
						$ttl += $table['l'][$k];
						$widthcols[$k]['miw'] += ($wi * $spareratio);
					}

				}
			}
			else {
				$wi = ($table['w'] - ($wis + $tblbw));	//	i.e. extra space to distribute
				foreach ($list as $k) {
					$spareratio = ($table['l'][$k] / $totalatextlength); //  gives ratio to divide up free space
					// Don't allocate more than Maximum required width - save rest in surplus
					if ($widthcols[$k]['miw'] + ($wi * $spareratio) > $widthcols[$k]['maw']) {
						$surplus += ($wi * $spareratio) - ($widthcols[$k]['maw']-$widthcols[$k]['miw']);
						$widthcols[$k]['miw'] = $widthcols[$k]['maw'];
					}
					else {
						$notsetlist[] = $k;
						$ttl += $table['l'][$k];
						$widthcols[$k]['miw'] += ($wi * $spareratio);
					}
				}
			}
			// If surplus still left over apportion it across columns
			if ($surplus) {
			   // if some are set only add to remaining - otherwise add to all of them
			   if (count($notsetlist) && count($notsetlist) < $numcols) {
				foreach ($notsetlist AS $i) {
					if ($ttl) $widthcols[$i]['miw'] += $surplus * $table['l'][$i] / $ttl ;
				}
			   }
			   // If some widths are defined, and others have been added up to their maxmum
			   else if (count($list) && count($list) < $numcols) {
				foreach ($list AS $i) {
					$widthcols[$i]['miw'] += $surplus / count($list) ;
				}
			   }
			   else if ($numcols) {	// If all columns
				$ttl = array_sum($table['l']);
				for ($i=0;$i<$numcols;$i++) {
					$widthcols[$i]['miw'] += $surplus * $table['l'][$i] / $ttl;
				}
			   }
			}

		}

		// This sets the columns all to minimum width (which has been increased above if appropriate)
		for ($i=0;$i<$numcols;$i++) {
			$widthcols[$i] = $widthcols[$i]['miw'];
		}

		// TABLE NOT WIDE ENOUGH EVEN FOR MINIMUM CONTENT WIDTH
		// If sum of column widths set are too wide for table
		$checktablewidth = 0;
		for ( $i = 0 ; $i < $numcols ; $i++ ) {
			$checktablewidth += $widthcols[$i];
		}
		if ($checktablewidth > ($temppgwidth + 0.001 - $tblbw)) {
		   $usedup = 0; $numleft = 0;
		   for ($i=0;$i<$numcols;$i++) {
			if ((isset($widthcols[$i]) && $widthcols[$i] > (($temppgwidth - $tblbw) / $numcols)) && (!isset($widthcols[$i]['w']))) {
				$numleft++;
				unset($widthcols[$i]);
			}
			else { $usedup += $widthcols[$i]; }
		   }
		   for ($i=0;$i<$numcols;$i++) {
			if (!isset($widthcols[$i]) || !$widthcols[$i]) {
				$widthcols[$i] = ((($temppgwidth - $tblbw) - $usedup)/ ($numleft));
			}
		   }
		}

	}
	else { //table has no width defined
		$table['w'] = $tablewidth;
		for ( $i = 0 ; $i < $numcols ; $i++) {
			if (isset($widthcols[$i]['wpercent']) && $this->keep_table_proportions)  { $colwidth = $widthcols[$i]['maw']; }
			else if (isset($widthcols[$i]['w']))  { $colwidth = $widthcols[$i]['miw']; }
			else { $colwidth = $widthcols[$i]['maw']; }
			unset($widthcols[$i]);
			$widthcols[$i] = $colwidth;
		}
	}

	if ($table['overflow']=='visible' && $table['level'] ==1) {
		if ($tablewidth > $this->blk[$this->blklvl]['inner_width']) {
		  if ($this->cacheTables) { $fh = fopen($table['cache'], "r+b"); }
		  else { $fh = null; }
		  for ($j = 0 ; $j < $numcols; $j++) { //columns
			for ($i = 0 ; $i < $table['nr']; $i++) { //rows
				if (isset($table['cells'][$i][$j]) && $table['cells'][$i][$j]) {
				  if ($this->cacheTables) {
					$cc = $this->_uncacheCell($table['cells'][$i][$j], '', $fh);
					$colspan = $cc['colspan'];
				  }
				  else
				  	$colspan = $table['cells'][$i][$j]['colspan'];
				  if ($colspan > 1) {
					$w = 0;
					for ($c = $j; $c < ($j + $colspan); $c++) {
						$w += $widthcols[$c];
					}
					if ($w > $this->blk[$this->blklvl]['inner_width']) {
						$diff = $w - ($this->blk[$this->blklvl]['inner_width'] - $tblbw);
						for ($c = $j; $c < ($j + $colspan); $c++) {
							$widthcols[$c] -= $diff * ($widthcols[$c]/$w);
						}
						$table['w'] -= $diff;
						$table['csp'][$j] = $w - $diff;
					}
				  }
				}

			}
		  }
		  if ($this->cacheTables) { fclose($fh); }
		}
		$pgNo = 0;
		$currWc = 0;
		for ($i = 0 ; $i < $numcols; $i++) { //columns
			if (isset($table['csp'][$i])) {
				$w = $table['csp'][$i];
				unset($table['csp'][$i]);
			}
			else { $w = $widthcols[$i]; }
			if (($currWc + $w + $tblbw) > $this->blk[$this->blklvl]['inner_width']) {
				$pgNo++;
				$currWc = $widthcols[$i] ;
			}
			else { $currWc += $widthcols[$i] ; }
			$table['colPg'][$i] = $pgNo;
		}
	}
}



function _tableHeight(&$table){
	$level = $table['level'];
	$levelid = $table['levelid'];
	$cells = &$table['cells'];
	$numcols = $table['nc'];
	$numrows = $table['nr'];
	$listspan = array();
	$checkmaxheight = 0;
	$headerrowheight = 0;
	$checkmaxheightplus = 0;
	$headerrowheightplus = 0;
	$firstrowheight = 0;

	$footerrowheight = 0;
	$footerrowheightplus = 0;
	if ($this->table_rotate) {
		$temppgheight = $this->tbrot_maxh;
		$remainingpage = $this->tbrot_maxh;
	}
	else {
		$temppgheight = ($this->h - $this->bMargin - $this->tMargin) - $this->kwt_height;
		$remainingpage = ($this->h - $this->bMargin - $this->y) - $this->kwt_height;

		// If it is less than 1/20th of the remaining page height to finish the DIV (i.e. DIV padding + table bottom margin)
		// then allow for this
		$enddiv = $this->blk[$this->blklvl]['padding_bottom'] + $this->blk[$this->blklvl]['border_bottom']['w'] + $table['margin']['B'];
		if ($remainingpage > $enddiv && $enddiv/$remainingpage <0.05) { $remainingpage -= $enddiv; }
		else if ($remainingpage == 0) { $remainingpage = 0.001; }
		if ($temppgheight > $enddiv && $enddiv/$temppgheight <0.05) { $temppgheight -= $enddiv; }
		else if ($temppgheight == 0) { $temppgheight = 0.001; }
	}

	if ($this->cacheTables) { $fh = fopen($table['cache'], "r+b"); }
	else { $fh = null; }

	for( $i = 0 ; $i < $numrows ; $i++ ) { //rows
		$heightrow = &$table['hr'][$i];
		for( $j = 0 ; $j < $numcols ; $j++ ) { //columns
			if (isset($cells[$i][$j]) && $cells[$i][$j]) {
				if ($this->cacheTables) {
					$c = $this->_uncacheCell($table['cells'][$i][$j], '', $fh);
				}
				else
					$c = &$cells[$i][$j];

				if ($this->simpleTables){
				   if ($table['borders_separate']) {	// NB twice border width
					$extraWLR = ($table['simple']['border_details']['L']['w']+$table['simple']['border_details']['R']['w']) + ($c['padding']['L']+$c['padding']['R'])+$table['border_spacing_H'];
					$extrh = ($table['simple']['border_details']['T']['w']+$table['simple']['border_details']['B']['w']) + ($c['padding']['T']+$c['padding']['B'])+$table['border_spacing_V'];
				   }
				   else {
					$extraWLR = ($table['simple']['border_details']['L']['w']+$table['simple']['border_details']['R']['w'])/2 + ($c['padding']['L']+$c['padding']['R']);
					$extrh = ($table['simple']['border_details']['T']['w']+$table['simple']['border_details']['B']['w'])/2 + ($c['padding']['T']+$c['padding']['B']);
				   }
				}
				else  {
			 	   if ($this->packTableData) {
			 	   	list($bt,$br,$bb,$bl) = $this->_getBorderWidths($c['borderbin']);
			 	   }
			 	   else {
					$bt = $c['border_details']['T']['w'];
					$bb = $c['border_details']['B']['w'];
					$br = $c['border_details']['R']['w'];
					$bl = $c['border_details']['L']['w'];
				   }
				   if ($table['borders_separate']) {	// NB twice border width
					$extraWLR = $bl + $br + $c['padding']['L'] + $c['padding']['R'] + $table['border_spacing_H'];
					$extrh = $bt + $bb + $c['padding']['T'] + $c['padding']['B'] + $table['border_spacing_V'];
				   }
				   else {
					$extraWLR = $bl/2 + $br/2 + $c['padding']['L'] + $c['padding']['R'];
					$extrh = $bt/2 + $bb/2 + $c['padding']['T']+$c['padding']['B'];
				   }
				}

				if ($table['overflow']=='visible' && $level==1)
					list($x,$cw) = $this->_splitTableGetWidth($table, $i,$j, $fh);
				else
					list($x,$cw) = $this->_tableGetWidth($table, $i,$j, $fh);

				// Get CELL HEIGHT
				// ++ extra parameter forces wrap to break word
				if ($c['R'] && isset($c['textbuffer'])) {	// mPDF 5.4.01
					$str = '';
					foreach($c['textbuffer'] AS $t) { $str .= $t[0].' '; }
					$str = trim($str);
					$s_fs = $this->FontSizePt;
					$s_f = $this->FontFamily;
					$s_st = $this->FontStyle;
					$this->SetFont($c['textbuffer'][0][4],$c['textbuffer'][0][2],$c['textbuffer'][0][11] / $this->shrin_k,true,true);
	       			$tempch = $this->GetStringWidth($str);
					if ($c['R'] >= 45 && $c['R'] < 90) {
						$tempch = ((sin(deg2rad($c['R']))) * $tempch ) + ((sin(deg2rad($c['R']))) * (($c['textbuffer'][0][11]/_MPDFK) / $this->shrin_k));
					}
					$this->SetFont($s_f,$s_st,$s_fs,true,true);
					$ch = ($tempch ) + $extrh ;
				}
				else {
				   if (isset($c['textbuffer'])) {
					$tempch = $this->TableWordWrap(($cw-$extraWLR),1,$c['textbuffer'], $c['dfs']);
				   }
				   else { $tempch = 0; }

					// Added cellpadding top and bottom. (Lineheight already adjusted to table_lineheight)
					$ch = $tempch + $extrh ;
				}
				//If height is defined and it is bigger than calculated $ch then update values
				if (isset($c['h']) && $c['h'] > $ch) {
					$c['mih'] = $ch; //in order to keep valign working
					$ch = $c['h'];
				}
				else $c['mih'] = $ch;
				if ($this->cacheTables) {
					$this->_cacheUpdateMtx($c, $fh, $table['cells'][$i][$j], 'mih');
				}
				if (isset($c['rowspan']))	$listspan[] = array($i,$j);
				elseif ($heightrow < $ch) $heightrow = $ch;

				// this is the extra used in _tableWrite to determine whether to trigger a page change
				if ($table['borders_separate']) {
				  if ($i == ($numrows-1) || (isset($c['rowspan']) && ($i+$c['rowspan']) == ($numrows)) ) {
					$extra = $table['margin']['B'] + $table['padding']['B'] + $table['border_details']['B']['w'] + $table['border_spacing_V']/2;
				  }
				  else {
					$extra = $table['border_spacing_V']/2;
				  }
				}
	  			else {
					if (!$this->simpleTables){
						$extra = $bb/2;
					}
					else if ($this->simpleTables){
						$extra = $table['simple']['border_details']['B']['w'] /2;
					}
				}
				if (isset($table['is_thead'][$i]) && $table['is_thead'][$i]) {
				   if ($j==0) {
					$headerrowheight += $ch;
					$headerrowheightplus += $ch+$extra;
				   }
				}
				else if (isset($table['is_tfoot'][$i]) && $table['is_tfoot'][$i]) {
				   if ($j==0) {
					$footerrowheight += $ch;
					$footerrowheightplus += $ch+$extra;
				   }
				}
				else {
					$checkmaxheight = max($checkmaxheight,$ch);
					$checkmaxheightplus = max($checkmaxheightplus,$ch+$extra);
				}
				if ($this->tableLevel==1 && $i == $table['headernrows']) { $firstrowheight = max($ch,$firstrowheight); }
				unset($c);
			}
		}//end of columns
	}//end of rows

	$heightrow = &$table['hr'];
	foreach ($listspan as $span) {
		list($i,$j) = $span;
		if ($this->cacheTables) {
			$c = $this->_uncacheCell($table['cells'][$i][$j], '', $fh);
		}
		else
			$c = &$cells[$i][$j];
		$lr = $i + $c['rowspan'];
		if ($lr > $numrows) $lr = $numrows;
		$hs = $hsa = 0;
		$list = array();
		for($k=$i;$k<$lr;$k++) {
			$hs += $heightrow[$k];
			if (!isset($c['h'])) {
				$list[] = $k;
				$hsa += $heightrow[$k];
			}
		}

		if ($table['borders_separate']) {
		  if ($i == ($numrows-1) || ($i+$c['rowspan']) == ($numrows) ) {
			$extra = $table['margin']['B'] + $table['padding']['B'] + $table['border_details']['B']['w'] + $table['border_spacing_V']/2;
		  }
		  else {
			$extra = $table['border_spacing_V']/2;
		  }
		}
	  	else {
			if (!$this->simpleTables){
			 	if ($this->packTableData) {
			 		list($bt,$br,$bb,$bl) = $this->_getBorderWidths($c['borderbin']);
			 	}
			 	else {
					$bb = $c['border_details']['B']['w'];
				}
				$extra = $bb/2;
			}
			else if ($this->simpleTables){
				$extra = $table['simple']['border_details']['B']['w'] /2;
			}
		}
		if (!empty($table['is_thead'][$i])) {
			$headerrowheight = max($headerrowheight,$hs);
			$headerrowheightplus = max($headerrowheightplus,$hs+$extra);
		}
		else if (!empty($table['is_tfoot'][$i])) {
			$footerrowheight = max($footerrowheight,$hs);
			$footerrowheightplus = max($footerrowheightplus,$hs+$extra);
		}
		else {
			$checkmaxheight = max($checkmaxheight,$hs);
			$checkmaxheightplus = max($checkmaxheightplus,$hs+$extra);
		}
		if ($this->tableLevel==1 && $i == $table['headernrows']) { $firstrowheight = max($hs,$firstrowheight); }

		if ($c['mih'] > $hs) {
			if (!$hs) {
				for($k=$i;$k<$lr;$k++) $heightrow[$k] = $c['mih']/$c['rowspan'];
			}
			elseif (!count($list)) {
				$hi = $c['mih'] - $hs;
				for($k=$i;$k<$lr;$k++) $heightrow[$k] += ($heightrow[$k]/$hs)*$hi;
			}
			else {
				$hi = $c['mih'] - $hsa;
				foreach ($list as $k) $heightrow[$k] += ($heightrow[$k]/$hsa)*$hi;
			}
		}
		unset($c);

		// If rowspans overlap so that one or more rows do not have a height set...
		// i.e. for one or more rows, the only cells (explicit) in that row have rowspan>1
		// so heightrow is still == 0
		if ($heightrow[$i]==0) {
			// Get row extent to analyse above and below
			$top = $i;
			foreach ($listspan as $checkspan) {
				list($cki,$ckj) = $checkspan;
				if ($this->cacheTables) {
					$c = $this->_uncacheCell($table['cells'][$cki][$ckj], '', $fh);
				}
				else
					$c = &$cells[$cki][$ckj];
				if (isset($c['rowspan']) && $c['rowspan']>1) {
					if (($cki + $c['rowspan']-1) >= $i) { $top = min($top, $cki); }
				}
			}
			$bottom = $i + $c['rowspan']-1;
			// Check for overconstrained conditions
			for ($k=$top; $k<=$bottom; $k++) {
				// if ['hr'] for any of the others is also 0, then abort (too complicated)
				if ($k != $i && $heightrow[$k]==0) { break(1); }
				// check again that top and bottom are not crossed by rowspans - or abort (too complicated)
				if ($k==$top) {
					// ???? take account of colspan as well???
					for( $m = 0 ; $m < $numcols ; $m++ ) { //columns
						if (!isset($cells[$k][$m]) || $cells[$k][$m]==0) {
							break(2);
						}
					}
				}
				else if ($k==$bottom) {
					// ???? take account of colspan as well???
					for( $m = 0 ; $m < $numcols ; $m++ ) { //columns
						if ($this->cacheTables) {
							$c = $this->_uncacheCell($table['cells'][$k][$m], '', $fh);
						}
						else
							$c = &$cells[$k][$m];
						if (isset($c['rowspan']) && $c['rowspan']>1) {
							break(2);
						}
					}
				}
			}
			// By columns add up col height using ['h'] if set or ['mih'] if not
			// Intentionally do not substract border-spacing
			$colH = array();
			$extH = 0;
			$newhr = array();
			for( $m = 0 ; $m < $numcols ; $m++ ) { //columns
			   for ($k=$top; $k<=$bottom; $k++) {
				if (isset($cells[$k][$m]) && $cells[$k][$m]!=0) {
					if ($this->cacheTables) {
						$c = $this->_uncacheCell($table['cells'][$k][$m], '', $fh);
					}
					else
						$c = &$cells[$k][$m];
					if (isset($c['h']) && $c['h']) {
						$useh = $c['h'];
					}
					// ???? take account of colspan as well???
					else {
						$useh = $c['mih'];
					}
					$colH[$m] += $useh;
					if (!isset($c['rowspan']) || $c['rowspan']<2) { $newhr[$k] = max($newhr[$k], $useh); }
				}
			   }
			   $extH = max($tabH, $colH[$m]);
			}
			$newhr[$i] = $extH - array_sum($newhr);
			for ($k=$top; $k<=$bottom; $k++) { $heightrow[$k] = $newhr[$k]; }
		}


		unset($c);
	}

	$table['h'] = array_sum($heightrow);
	unset($heightrow);

	if ($this->cacheTables) { fclose($fh); }

	if ($table['borders_separate']) {
		$table['h'] += $table['margin']['T'] + $table['margin']['B'] + $table['border_details']['T']['w'] + $table['border_details']['B']['w'] + $table['border_spacing_V'] + $table['padding']['T'] +  $table['padding']['B'];
	}
	else {
		$table['h'] += $table['margin']['T'] + $table['margin']['B'] + $table['max_cell_border_width']['T']/2 + $table['max_cell_border_width']['B']/2;
	}

	$maxrowheight = $checkmaxheightplus + $headerrowheightplus + $footerrowheightplus;
	$maxfirstrowheight = $firstrowheight + $headerrowheightplus + $footerrowheightplus;	// includes thead, 1st row and tfoot
	return array($table['h'],$maxrowheight,$temppgheight,$remainingpage,$maxfirstrowheight);
}

function _tableGetWidth(&$table, $i,$j, $fh){
	if ($this->cacheTables) {
		$cell = $this->_uncacheCell($table['cells'][$i][$j], '', $fh);
	}
	else
		$cell = &$table['cells'][$i][$j];
	if ($cell) {
		if (isset($cell['x0'])) { return array($cell['x0'], $cell['w0']); }
		$x = 0;
		$widthcols = &$table['wc'];
		for( $k = 0 ; $k < $j ; $k++ ) $x += $widthcols[$k];
		$w = $widthcols[$j];
		if (isset($cell['colspan'])) {
			 for ( $k = $j+$cell['colspan']-1 ; $k > $j ; $k-- )	$w += $widthcols[$k];
		}
		$cell['x0'] = $x;
		$cell['w0'] = $w;
		if ($this->cacheTables) {
			$this->_cacheUpdateMtx($cell, $fh, $table['cells'][$i][$j], 'x0');
		}
		return array($x, $w);
	}
	return array(0,0);
}

function _splitTableGetWidth(&$table, $i,$j, $fh){
	if ($this->cacheTables) {
		$cell = $this->_uncacheCell($table['cells'][$i][$j], '', $fh);
	}
	else
		$cell = &$table['cells'][$i][$j];
	if ($cell) {
		if (isset($cell['x0'])) return array($cell['x0'], $cell['w0']);
		$x = 0;
		$widthcols = &$table['wc'];
		$pg = $table['colPg'][$j];
		for( $k = 0 ; $k < $j ; $k++ ) {
			if ($table['colPg'][$k]==$pg) $x += $widthcols[$k];
		}
		$w = $widthcols[$j];
		if (isset($cell['colspan'])) {
			 for ( $k = $j+$cell['colspan']-1 ; $k > $j ; $k-- )	if ($table['colPg'][$k]==$pg) $w += $widthcols[$k];
		}
		$cell['x0'] = $x;
		$cell['w0'] = $w;
		if ($this->cacheTables) {
			$this->_cacheUpdateMtx($cell, $fh, $table['cells'][$i][$j], 'x0');
		}
		return array($x, $w);
	}
	return array(0,0);
}


function _tableGetHeight(&$table, $i,$j, $fh){
	if ($this->cacheTables) {
		$cell = $this->_uncacheCell($table['cells'][$i][$j], '', $fh);
	}
	else
		$cell = &$table['cells'][$i][$j];
	if ($cell){
		if (isset($cell['y0'])) return array($cell['y0'], $cell['h0']);
		$y = 0;
		$heightrow = &$table['hr'];
		for ($k=0;$k<$i;$k++) $y += $heightrow[$k];
		$h = $heightrow[$i];
		if (isset($cell['rowspan'])){
			for ($k=$i+$cell['rowspan']-1;$k>$i;$k--)
				$h += $heightrow[$k];
		}
		$cell['y0'] = $y;
		$cell['h0'] = $h;
		if ($this->cacheTables) {
			$this->_cacheUpdateMtx($cell, $fh, $table['cells'][$i][$j], 'y0');
		}
		return array($y, $h);
	}
	return array(0,0);
}

function _tableGetMaxRowHeight($table, $row, $fh) {
	if ($row==$table['nc']-1) { return $table['hr'][$row]; }
	$maxrowheight = $table['hr'][$row];
	for ($i=$row+1;$i<$table['nr'];$i++) {
		$cellsset = 0;
		for ($j=0;$j<$table['nc'];$j++) {
			if ($this->cacheTables) {
				$cell = $this->_uncacheCell($table['cells'][$i][$j], '', $fh);
				if ($cell) {
					if (isset($cell['colspan'])) {  $cellsset += $cell['colspan']; }
					else $cellsset += 1;
				}
			}
			else {
				if ($table['cells'][$i][$j]) {
					if (isset($table['cells'][$i][$j]['colspan'])) {  $cellsset += $table['cells'][$i][$j]['colspan']; }
					else $cellsset += 1;
				}
			}
		}
		if ($cellsset == $table['nc']) { return $maxrowheight; }
		else { $maxrowheight += $table['hr'][$i]; }
	}
	return $maxrowheight;
}


// CHANGED TO ALLOW TABLE BORDER TO BE SPECIFIED CORRECTLY - added border_details
function _tableRect($x, $y, $w, $h, $bord=-1, $details=array(), $buffer=false, $bSeparate=false, $cort='cell', $tablecorner='', $bsv=0, $bsh=0) {

	$cellBorderOverlay = array();

	if ($bord==-1) { $this->Rect($x, $y, $w, $h); }
	else if ($this->simpleTables && ($cort=='cell')) {
		$this->SetLineWidth($details['L']['w']);
		if ($details['L']['c']) {
			$this->SetDColor($details['L']['c']);
		}
		else { $this->SetDColor($this->ConvertColor(0)); }
		$this->SetLineJoin(0);
		$this->Rect($x, $y, $w, $h);
	}
	else if ($bord){
	   if (!$bSeparate && $buffer) {
		$priority = 'LRTB';
		for($p=0;$p<strlen($priority);$p++) {
			$side = $priority[$p];
			$details['p'] = $side ;

			$dom = 0;
			if (isset($details[$side]['w'])) { $dom += ($details[$side]['w'] * 100000); }
			if (isset($details[$side]['style'])) { $dom += (array_search($details[$side]['style'],$this->borderstyles)*100) ; }
			if (isset($details[$side]['dom'])) { $dom += ($details[$side]['dom']*10); }

			// Precedence to darker colours at joins
			$coldom = 0;
			if (isset($details[$side]['c']) && is_array($details[$side]['c'])) {
				if ($details[$side]['c']{0}==3) { 	// RGB
					$coldom = 10-(((ord($details[$side]['c']{1})*1.00)+(ord($details[$side]['c']{2})*1.00)+(ord($details[$side]['c']{3})*1.00))/76.5);
				}
			} // 10 black - 0 white
			if ($coldom) { $dom += $coldom; }
			// Lastly precedence to RIGHT and BOTTOM cells at joins
			if (isset($details['cellposdom'])) { $dom += $details['cellposdom']; }

			$save = false;
			if ($side == 'T' && $this->issetBorder($bord, _BORDER_TOP)) { $cbord = _BORDER_TOP; $save = true; }
			else if ($side == 'L' && $this->issetBorder($bord, _BORDER_LEFT)) { $cbord = _BORDER_LEFT; $save = true; }
			else if ($side == 'R' && $this->issetBorder($bord, _BORDER_RIGHT)) { $cbord = _BORDER_RIGHT; $save = true; }
			else if ($side == 'B' && $this->issetBorder($bord, _BORDER_BOTTOM)) { $cbord = _BORDER_BOTTOM; $save = true; }

			if ($save) {
				$this->cellBorderBuffer[] = pack("A16nCnda6A10d14",
					str_pad(sprintf("%08.7f", $dom),16,"0",STR_PAD_LEFT),
					$cbord,
					ord($side),
					$details[$side]['s'],
					$details[$side]['w'],
					$details[$side]['c'],
					$details[$side]['style'],
					$x, $y, $w, $h,
					$details['mbw']['BL'],
					$details['mbw']['BR'],
					$details['mbw']['RT'],
					$details['mbw']['RB'],
					$details['mbw']['TL'],
					$details['mbw']['TR'],
					$details['mbw']['LT'],
					$details['mbw']['LB'],
					$details['cellposdom'],
					0
				);
			   if ($details[$side]['style'] == 'ridge' || $details[$side]['style'] == 'groove' || $details[$side]['style'] == 'inset' || $details[$side]['style'] == 'outset' || $details[$side]['style'] == 'double' ) {
				$details[$side]['overlay'] = true;
				$this->cellBorderBuffer[] = pack("A16nCnda6A10d14",
					str_pad(sprintf("%08.7f", ($dom+4)),16,"0",STR_PAD_LEFT),
					$cbord,
					ord($side),
					$details[$side]['s'],
					$details[$side]['w'],
					$details[$side]['c'],
					$details[$side]['style'],
					$x, $y, $w, $h,
					$details['mbw']['BL'],
					$details['mbw']['BR'],
					$details['mbw']['RT'],
					$details['mbw']['RB'],
					$details['mbw']['TL'],
					$details['mbw']['TR'],
					$details['mbw']['LT'],
					$details['mbw']['LB'],
					$details['cellposdom'],
					1
				);
			   }
			}
		}
		return;
	   }

	   if (isset($details['p']) && strlen($details['p'])>1) { $priority = $details['p']; }
	   else { $priority='LTRB'; }
	   $Tw = 0;
	   $Rw = 0;
	   $Bw = 0;
	   $Lw = 0;
		if (isset($details['T']['w'])) { $Tw = $details['T']['w']; }
		if (isset($details['R']['w'])) { $Rw = $details['R']['w']; }
		if (isset($details['B']['w'])) { $Bw = $details['B']['w']; }
		if (isset($details['L']['w'])) { $Lw = $details['L']['w']; }

	   $x2 = $x + $w; $y2 = $y + $h;
	   $oldlinewidth = $this->LineWidth;

	   for($p=0;$p<strlen($priority);$p++) {
		$side = $priority[$p];
		$xadj = 0;
		$xadj2 = 0;
		$yadj = 0;
		$yadj2 = 0;
		$print = false;
		if ($Tw && $side=='T' && $this->issetBorder($bord, _BORDER_TOP)) {	// TOP
			$ly1 = $y;
			$ly2 = $y;
			$lx1 = $x;
			$lx2 = $x2;
			$this->SetLineWidth($Tw);
			if ($cort == 'cell' || strpos($tablecorner,'L')!==false) {
				if ($Tw > $Lw) $xadj = ($Tw - $Lw)/2;
				if ($Tw < $Lw) $xadj = ($Tw + $Lw)/2;
			}
			else { $xadj = $Tw/2 - $bsh/2; }
			if ($cort == 'cell' || strpos($tablecorner,'R')!==false) {
				if ($Tw > $Rw) $xadj2 = ($Tw - $Rw)/2;
				if ($Tw < $Rw) $xadj2 = ($Tw + $Rw)/2;
			}
			else { $xadj2 = $Tw/2 - $bsh/2; }
			if (!$bSeparate && $details['mbw']['TL']) {
				$xadj = ($Tw - $details['mbw']['TL'])/2 ;
			}
			if (!$bSeparate && $details['mbw']['TR']) {
				$xadj2 = ($Tw - $details['mbw']['TR'])/2;
			}
			$print = true;
		}
		if ($Lw && $side=='L' && $this->issetBorder($bord, _BORDER_LEFT)) {	// LEFT
			$ly1 = $y;
			$ly2 = $y2;
			$lx1 = $x;
			$lx2 = $x;
			$this->SetLineWidth($Lw);
			if ($cort == 'cell' || strpos($tablecorner,'T')!==false) {
				if ($Lw > $Tw) $yadj = ($Lw - $Tw)/2;
				if ($Lw < $Tw) $yadj = ($Lw + $Tw)/2;
			}
			else { $yadj = $Lw/2 - $bsv/2; }
			if ($cort == 'cell' || strpos($tablecorner,'B')!==false) {
				if ($Lw > $Bw) $yadj2 = ($Lw - $Bw)/2;
				if ($Lw < $Bw) $yadj2 = ($Lw + $Bw)/2;
			}
			else { $yadj2 = $Lw/2 - $bsv/2; }
			if (!$bSeparate && $details['mbw']['LT']) {
				$yadj = ($Lw - $details['mbw']['LT'])/2;
			}
			if (!$bSeparate && $details['mbw']['LB']) {
				$yadj2 = ($Lw - $details['mbw']['LB'])/2;
			}
			$print = true;
		}
		if ($Rw && $side=='R' && $this->issetBorder($bord, _BORDER_RIGHT)) {	// RIGHT
			$ly1 = $y;
			$ly2 = $y2;
			$lx1 = $x2;
			$lx2 = $x2;
			$this->SetLineWidth($Rw);
			if ($cort == 'cell' || strpos($tablecorner,'T')!==false) {
				if ($Rw < $Tw) $yadj = ($Rw + $Tw)/2;
				if ($Rw > $Tw) $yadj = ($Rw - $Tw)/2;
			}
			else { $yadj = $Rw/2 - $bsv/2; }

			if ($cort == 'cell' || strpos($tablecorner,'B')!==false) {
				if ($Rw > $Bw) $yadj2 = ($Rw - $Bw)/2;
				if ($Rw < $Bw) $yadj2 = ($Rw + $Bw)/2;
			}
			else { $yadj2 = $Rw/2 - $bsv/2; }

			if (!$bSeparate && $details['mbw']['RT']) {
				$yadj = ($Rw - $details['mbw']['RT'])/2;
			}
			if (!$bSeparate && $details['mbw']['RB']) {
				$yadj2 = ($Rw - $details['mbw']['RB'])/2;
			}
			$print = true;
		}
		if ($Bw && $side=='B' && $this->issetBorder($bord, _BORDER_BOTTOM)) {	// BOTTOM
			$ly1 = $y2;
			$ly2 = $y2;
			$lx1 = $x;
			$lx2 = $x2;
			$this->SetLineWidth($Bw);
			if ($cort == 'cell' || strpos($tablecorner,'L')!==false) {
				if ($Bw > $Lw) $xadj = ($Bw - $Lw)/2;
				if ($Bw < $Lw) $xadj = ($Bw + $Lw)/2;
			}
			else { $xadj = $Bw/2 - $bsh/2; }
			if ($cort == 'cell' || strpos($tablecorner,'R')!==false) {
				if ($Bw > $Rw) $xadj2 = ($Bw - $Rw)/2;
				if ($Bw < $Rw) $xadj2 = ($Bw + $Rw)/2;
			}
			else { $xadj2 = $Bw/2 - $bsh/2; }
			if (!$bSeparate && $details['mbw']['BL']) {
				$xadj = ($Bw - $details['mbw']['BL'])/2;
			}
			if (!$bSeparate && $details['mbw']['BR']) {
				$xadj2 = ($Bw - $details['mbw']['BR'])/2;
			}
			$print = true;
		}

		// Now draw line
		if ($print) {
		   if ($details[$side]['style'] == 'dashed') {
			$dashsize = 2;	// final dash will be this + 1*linewidth
			$dashsizek = 1.5;	// ratio of Dash/Blank
			$this->SetDash($dashsize,($dashsize/$dashsizek)+($this->LineWidth*2));
		   }
		   else if ($details[$side]['style'] == 'dotted') {
			$this->SetLineJoin(1);
			$this->SetLineCap(1);
			$this->SetDash(0.001,($this->LineWidth*2));
		   }
		   if ($details[$side]['c']) {
			$this->SetDColor($details[$side]['c']);
		   }
		   else { $this->SetDColor($this->ConvertColor(0)); }
		   $this->Line($lx1 + $xadj, $ly1 + $yadj, $lx2 - $xadj2, $ly2 - $yadj2);

	   	  // Reset Corners
	   	  $this->SetDash();
  		  //BUTT style line cap
		  $this->SetLineCap(2);
		}
	   }

	   if ($bSeparate && count($cellBorderOverlay)) {
		foreach($cellBorderOverlay AS $cbo) {
			$this->SetLineWidth($cbo['lw']);
			$this->SetDColor($cbo['col']);
			$this->Line($cbo['x'], $cbo['y'], $cbo['x2'], $cbo['y2']);
		}
	   }

	   // $this->SetLineWidth($oldlinewidth);
	   // $this->SetDColor($this->ConvertColor(0));
	}
}





function setBorder(&$var, $flag, $set = true) {
	$flag = intval($flag);
	if ($set) { $set = true; }
	$var = intval($var);
	$var = $set ? ($var | $flag) : ($var & ~$flag);
}
function issetBorder($var, $flag) {
	$flag = intval($flag);
	$var = intval($var);
	return (($var & $flag) == $flag);
}


function _table2cellBorder(&$tableb, &$cbdb, &$cellb, $bval) {
	if ($tableb && $tableb['w'] > $cbdb['w']) {
		$cbdb = $tableb;
		$this->setBorder($cellb, $bval);
	}
	else if ($tableb && $tableb['w'] == $cbdb['w']
		&& array_search($tableb['style'],$this->borderstyles) > array_search($cbdb['style'],$this->borderstyles)) {
		$cbdb = $tableb;
		$this->setBorder($cellb, $bval);
	}
}

// FIX BORDERS ********************************************
function _fixTableBorders(&$table){
	if ($this->cacheTables) { $fh = fopen($table['cache'], "r+b"); }
	else { $fh = null; }

	if (!$table['borders_separate'] && $table['border_details']['L']['w']) {
		$table['max_cell_border_width']['L'] = $table['border_details']['L']['w'];
	}
	if (!$table['borders_separate'] && $table['border_details']['R']['w']) {
		$table['max_cell_border_width']['R'] = $table['border_details']['R']['w'];
	}
	if (!$table['borders_separate'] && $table['border_details']['T']['w']) {
		$table['max_cell_border_width']['T'] = $table['border_details']['T']['w'];
	}
	if (!$table['borders_separate'] && $table['border_details']['B']['w']) {
		$table['max_cell_border_width']['B'] = $table['border_details']['B']['w'];
	}
	if ($this->simpleTables) { return; }
	$cells = &$table['cells'];
	$numcols = $table['nc'];
	$numrows = $table['nr'];

	for( $i = 0 ; $i < $numrows ; $i++ ) { //Rows
	  for( $j = 0 ; $j < $numcols ; $j++ ) { //Columns
		if (isset($cells[$i][$j]) && $cells[$i][$j]) {
			if ($this->cacheTables) {
				$cell = $this->_uncacheCell($table['cells'][$i][$j], '', $fh);
			}
			else
				$cell = &$cells[$i][$j];
			if ($this->packTableData) {	// includes $this->cacheTables
				$cbord = $this->_unpackCellBorder($cell['borderbin']);
			}
			else {
				$cbord = &$cells[$i][$j];
			}

  			if (!$cbord['border'] && isset($table['border']) && $table['border'] && $this->table_border_attr_set) {
				$cbord['border'] = $table['border'];
				$cbord['border_details'] = $table['border_details'];
			}

			if (isset($cell['colspan']) && $cell['colspan']>1) { $ccolsp = $cell['colspan']; }
			else { $ccolsp = 1; }
			if (isset($cell['rowspan']) && $cell['rowspan']>1) { $crowsp = $cell['rowspan']; }
			else { $crowsp = 1; }

			$cbord['border_details']['cellposdom'] = ((($i+1)/$numrows) / 10000 ) + ((($j+1)/$numcols) / 10 );
			// Inherit Cell border from Table border
			if ($this->table_border_css_set && !$table['borders_separate']) {
				if ($i == 0) {
				  $this->_table2cellBorder($table['border_details']['T'], $cbord['border_details']['T'], $cbord['border'], _BORDER_TOP);
				}
				if ($i == ($numrows-1) || ($i+$crowsp) == ($numrows) ) {
				  $this->_table2cellBorder($table['border_details']['B'], $cbord['border_details']['B'], $cbord['border'], _BORDER_BOTTOM);
				}
				if ($j == 0) {
				  $this->_table2cellBorder($table['border_details']['L'], $cbord['border_details']['L'], $cbord['border'], _BORDER_LEFT);
				}
				if ($j == ($numcols-1) || ($j+$ccolsp) == ($numcols) ) {
				  $this->_table2cellBorder($table['border_details']['R'], $cbord['border_details']['R'], $cbord['border'], _BORDER_RIGHT);
				}
			}


			if ($this->packTableData) { $cell['borderbin'] = $this->_packCellBorder($cbord); }

			if ($this->cacheTables) {
				$this->_cacheUpdateBorder($cell, $fh, $table['cells'][$i][$j]);
			}
			unset($cbord );
			unset($cell );
		}
	  }
	}
	if ($this->cacheTables) { fclose($fh); }
	unset($cell );
}
// END FIX BORDERS ************************************************************************************


function _reverseTableDir(&$table) {
	if ($this->cacheTables) { $fh = fopen($table['cache'], "r+b"); }
	$cells = &$table['cells'];
	$numcols = $table['nc'];
	$numrows = $table['nr'];
	for( $i = 0 ; $i < $numrows ; $i++ ) { //Rows
		$row = array();
	  	for( $j = ($numcols-1) ; $j >= 0 ; $j-- ) { //Columns
			if (isset($cells[$i][$j]) && $cells[$i][$j]) {
				if ($this->cacheTables) {
					$cell = $this->_uncacheCell($table['cells'][$i][$j], '', $fh);
				}
				else
					$cell = &$cells[$i][$j];
				$col = $numcols - $j - 1;
				if (isset($cell['colspan']) && $cell['colspan'] > 1) { $col -= ($cell['colspan']-1); }
				// Nested content
				for ($n=0; $n < count($cell['textbuffer']); $n++) {
					$t = $cell['textbuffer'][$n][0];
					if (substr($t,0,19) == "\xbb\xa4\xactype=nestedtable") {
						$objattr = $this->_getObjAttr($t);
						$objattr['col'] = $col;
						$cell['textbuffer'][$n][0] = "\xbb\xa4\xactype=nestedtable,objattr=".serialize($objattr)."\xbb\xa4\xac";
						$this->table[($this->tableLevel+1)][$objattr['nestedcontent']]['nestedpos'][1] = $col;
						if ($this->cacheTables) {
							$this->_cacheUpdateTxB($cell, $fh, $table['cells'][$i][$j]);
						}
					}
				}
				$row[$col] = $cells[$i][$j];
				unset($cell);
			}
		}
	  	for($f=0; $f < $numcols; $f++) {
			if (!isset($row[$f])) { $row[$f] = 0; }
		}
		$table['cells'][$i] = $row;
	}
	if ($this->cacheTables) { fclose($fh); }
}


function _tableWrite(&$table, $split=false, $startrow=0, $startcol=0, $splitpg=0, $rety = 0){
	$level = $table['level'];
	$levelid = $table['levelid'];

	$cells = &$table['cells'];
	$numcols = $table['nc'];
	$numrows = $table['nr'];


	if (!$split || ($startrow==0 && $splitpg==0) || $startrow>0){
		// TABLE TOP MARGIN
		if ($table['margin']['T']) {
		   if (!$this->table_rotate && $level==1) {
			$this->DivLn($table['margin']['T'],$this->blklvl,true,1); 	// collapsible
		   }
		   else {
			$this->y += ($table['margin']['T']);
		   }
		}
		// Advance down page by half width of top border
		if ($table['borders_separate']) {
			if ($startrow>0 && (!isset($table['is_thead']) || count($table['is_thead'])==0))
				$adv = $table['border_spacing_V']/2;
			else
				$adv = $table['padding']['T'] + $table['border_details']['T']['w'] + $table['border_spacing_V']/2;
		}
		else {
			$adv = $table['max_cell_border_width']['T']/2;
		}
		if (!$this->table_rotate && $level==1) { $this->DivLn($adv); }
		else { $this->y += $adv; }
	}

	if ($level==1) {
		$this->x = $this->lMargin  + $this->blk[$this->blklvl]['outer_left_margin'] + $this->blk[$this->blklvl]['padding_left'] + $this->blk[$this->blklvl]['border_left']['w'];
		$x0 = $this->x;
		$y0 = $this->y;
		$right = $x0 + $this->blk[$this->blklvl]['inner_width'];
		$outerfilled = $this->y;	// Keep track of how far down the outer DIV bgcolor is painted (NB rowspans)
		$this->outerfilled = $this->y;
		$this->colsums = array();
	}
	else {
		$x0 = $this->x;
		$y0 = $this->y;
		$right = $x0 + $table['w'];
	}

	if ($this->table_rotate) {
		$temppgwidth = $this->tbrot_maxw;
		$this->PageBreakTrigger = $pagetrigger = $y0 + ($this->blk[$this->blklvl]['inner_width']);
	   if ($level==1) {
		$this->tbrot_y0 = $this->y - $adv - $table['margin']['T'] ;
		$this->tbrot_x0 = $this->x;
		$this->tbrot_w = $table['w'];
		if ($table['borders_separate']) { $this->tbrot_h = $table['margin']['T'] + $table['padding']['T'] + $table['border_details']['T']['w'] + $table['border_spacing_V']/2; }
		else { $this->tbrot_h = $table['margin']['T'] + $table['padding']['T'] + $table['max_cell_border_width']['T']; }
	   }
	}
	else {
		$this->PageBreakTrigger = $pagetrigger = ($this->h - $this->bMargin);
	   	if ($level==1) {
			$temppgwidth = $this->blk[$this->blklvl]['inner_width'];
	   		if (isset($table['a']) and ($table['w'] < $this->blk[$this->blklvl]['inner_width'])) {
				if ($table['a']=='C') { $x0 += ((($right-$x0) - $table['w'])/2); }
				else if ($table['a']=='R') { $x0 = $right - $table['w']; }
			}
	   	}
		else {
			$temppgwidth = $table['w'];
		}
	}
	if(!isset($table['overflow'])) { $table['overflow'] = null; }
	if ($table['overflow']=='hidden' && $level==1 && !$this->table_rotate && !$this->ColActive) {
		//Bounding rectangle to clip
		$this->tableClipPath = sprintf('q %.3F %.3F %.3F %.3F re W n',$x0*_MPDFK,$this->h*_MPDFK,$this->blk[$this->blklvl]['inner_width']*_MPDFK,-$this->h*_MPDFK);
		$this->_out($this->tableClipPath);
	}
	else { $this->tableClipPath = ''; }


	if ($table['borders_separate']) { $indent = $table['margin']['L'] + $table['border_details']['L']['w'] + $table['padding']['L'] + $table['border_spacing_H']/2; }
	else { $indent = $table['margin']['L'] + $table['max_cell_border_width']['L']/2; }
	$x0 += $indent;

	$returny = 0;
	$lastCol = 0;
	$tableheader = array();
	$tablefooter = array();
	$tableheaderrowheight = 0;
	$tablefooterrowheight = 0;
	$footery = 0;

	// mPD 3.0 Set the Page & Column where table starts
	if (($this->mirrorMargins) && (($this->page)%2==0)) {	// EVEN
		$tablestartpage = 'EVEN';
	}
	else if (($this->mirrorMargins) && (($this->page)%2==1)) {	// ODD
		$tablestartpage = 'ODD';
	}
	else { $tablestartpage = ''; }
	if ($this->ColActive) { $tablestartcolumn = $this->CurrCol; }
	else { $tablestartcolumn = ''; }

	if ($this->cacheTables) { $fh = fopen($table['cache'], "r+b"); }
	else { $fh = null; }

	$y = $h = 0;
	for( $i = 0; $i < $numrows ; $i++ ) { //Rows
	  if (isset($table['is_tfoot'][$i]) && $table['is_tfoot'][$i] && $level==1) {
		$tablefooterrowheight += $table['hr'][$i];
		$tablefooter[$i][0]['trbackground-images'] = $table['trbackground-images'][$i];
		$tablefooter[$i][0]['trgradients'] = $table['trgradients'][$i];
		$tablefooter[$i][0]['trbgcolor'] = $table['bgcolor'][$i];
	  	for( $j = $startcol ; $j < $numcols ; $j++ ) { //Columns
			if (isset($cells[$i][$j]) && $cells[$i][$j]) {
				if ($this->cacheTables) {
					$cell = $this->_uncacheCell($table['cells'][$i][$j], '', $fh);
				}
				else
					$cell = &$cells[$i][$j];
				if ($split) {
					if ($table['colPg'][$j] != $splitpg) { continue; }
					list($x,$w) = $this->_splitTableGetWidth($table, $i, $j, $fh);
					$js = $j - $startcol;
				}
				else {
					list($x,$w) = $this->_tableGetWidth($table, $i, $j, $fh);
					$js = $j;
				}

				list($y,$h) = $this->_tableGetHeight($table, $i, $j, $fh);
				$x += $x0;
				$y += $y0;
				//Get info of tfoot ==>> table footer
				$tablefooter[$i][$js]['x'] = $x;
				$tablefooter[$i][$js]['y'] = $y;
				$tablefooter[$i][$js]['h'] = $h;
				$tablefooter[$i][$js]['w'] = $w;
				if (isset($cell['textbuffer'])) { $tablefooter[$i][$js]['textbuffer'] = $cell['textbuffer']; }
				else { $tablefooter[$i][$js]['textbuffer'] = ''; }
				$tablefooter[$i][$js]['a'] = $cell['a'];
				$tablefooter[$i][$js]['R'] = $cell['R'];
				$tablefooter[$i][$js]['va'] = $cell['va'];
				$tablefooter[$i][$js]['mih'] = $cell['mih'];
				//CELL FILL BGCOLOR
				if (!$this->simpleTables){
			 		if ($this->packTableData) {
						$c = $this->_unpackCellBorder($cell['borderbin']);
						$tablefooter[$i][$js]['border'] = $c['border'];
						$tablefooter[$i][$js]['border_details'] = $c['border_details'];
					}
			 		else {
						$tablefooter[$i][$js]['border'] = $cell['border'];
						$tablefooter[$i][$js]['border_details'] = $cell['border_details'];
					}
				}
				else if ($this->simpleTables){
					$tablefooter[$i][$js]['border'] = $table['simple']['border'];
					$tablefooter[$i][$js]['border_details'] = $table['simple']['border_details'];
				}
				$tablefooter[$i][$js]['bgcolor'] = $cell['bgcolor'];
				$tablefooter[$i][$js]['padding'] = $cell['padding'];
				$tablefooter[$i][$js]['rowspan'] = $cell['rowspan'];
				$tablefooter[$i][$js]['colspan'] = $cell['colspan'];
			}
		}
	  }
	}

	if ($level==1) { $this->_out('___TABLE___BACKGROUNDS'.date('jY')); }
	$tableheaderadj = 0;
	$tablefooteradj = 0;

	$tablestartpageno = $this->page;

	//Draw Table Contents and Borders
	for( $i = 0; $i < $numrows ; $i++ ) { //Rows
	  if ($split && $startrow > 0) {
		$thnr = (isset($table['is_thead']) ? count($table['is_thead']) : 0);
		if ($i >= $thnr && $i < $startrow) { continue; }
		if ($i == $startrow){ $returny = $rety - $tableheaderrowheight; }
	  }

	  // Get Maximum row/cell height in row - including rowspan>1 + 1 overlapping
	  $maxrowheight = $this->_tableGetMaxRowHeight($table, $i, $fh);

	  $skippage = false;
	  $newpagestarted = false;
	  for( $j = $startcol ; $j < $numcols ; $j++ ) { //Columns
		if ($split) {
			if ($table['colPg'][$j] > $splitpg) { break; }
			$lastCol = $j;
		}
		if (isset($cells[$i][$j]) && $cells[$i][$j]) {
			if ($this->cacheTables) {
				$cell = $this->_uncacheCell($table['cells'][$i][$j], '', $fh);
			}
			else
				$cell = &$cells[$i][$j];
			if ($split) {
				$lastCol = $j + (isset($cell['colspan']) ? ($cell['colspan']-1) : 0) ;
				list($x,$w) = $this->_splitTableGetWidth($table, $i, $j, $fh);
			}
			else { list($x,$w) = $this->_tableGetWidth($table, $i, $j, $fh); }

			list($y,$h) = $this->_tableGetHeight($table, $i, $j, $fh);
			$x += $x0;
			$y += $y0;
			$y -= $returny;

			if ($table['borders_separate']) {
			  if (!empty($tablefooter) || $i == ($numrows-1) || (isset($cell['rowspan']) && ($i+$cell['rowspan']) == $numrows)  || (!isset($cell['rowspan']) && ($i+1) == $numrows) ) {
				$extra = $table['padding']['B'] + $table['border_details']['B']['w'] + $table['border_spacing_V']/2;
				//$extra = $table['margin']['B'] + $table['padding']['B'] + $table['border_details']['B']['w'] + $table['border_spacing_V']/2;
			  }
			  else {
				$extra = $table['border_spacing_V']/2;
			  }
			}
	  		else { $extra = $table['max_cell_border_width']['B']/2; }

			if ($j==$startcol  && ((($y + $maxrowheight + $extra ) > ($pagetrigger+0.001)) || (($this->keepColumns || !$this->ColActive) && !empty($tablefooter) && ($y + $maxrowheight + $tablefooterrowheight + $extra) > $pagetrigger) && ($this->tableLevel==1 && $i < ($numrows - $table['headernrows']))) && ($y0 >0 || $x0 > 0) && !$this->InFooter && $this->autoPageBreak ) {

				if (!$skippage) {
					$finalSpread = true;
					$firstSpread = true;
					if ($split) {
						for($t=$startcol; $t<$numcols; $t++) {
							// Are there more columns to print on a next page?
							if ($table['colPg'][$t] > $splitpg) {
								$finalSpread = false;
								break;
							}
						}
						if ($startcol>0) { $firstSpread = false; }
					}

		      		if (($this->keepColumns || !$this->ColActive) && !empty($tablefooter) && $i > 0 ) {
						$this->y = $y;
						$ya = $this->y;
						$this->TableHeaderFooter($tablefooter,$tablestartpage,$tablestartcolumn,'F',$level, $firstSpread, $finalSpread);
						if ($this->table_rotate) {
							$this->tbrot_h += $this->y - $ya ;
						}
						$tablefooteradj = $this->y - $ya ;
					}
					$y -= $y0;
					$returny += $y;

					$oldcolumn = $this->CurrCol;
					if ($this->AcceptPageBreak()) {
	  					$newpagestarted = true;
						$this->y = $y + $y0;

						// Move down to account for border-spacing or
						// extra half border width in case page breaks in middle
						if($i>0 && !$this->table_rotate && $level==1 && !$this->ColActive) {
							if ($table['borders_separate']) {
								$adv = $table['border_spacing_V']/2;
								// If table footer
								if (($this->keepColumns || !$this->ColActive) && !empty($tablefooter) && $i > 0 ) {
									$adv += ($table['padding']['B'] + $table['border_details']['B']['w']);
								}
							}
							else {
								$maxbwtop = 0;
								$maxbwbottom = 0;
								if (!$this->simpleTables){
									if (!empty($tablefooter)) { $maxbwbottom = $table['max_cell_border_width']['B']; }
									else {
									   $brow = $i-1;
									   for( $ctj = 0 ; $ctj < $numcols ; $ctj++ ) {
										if (isset($cells[$brow][$ctj]) && $cells[$brow][$ctj]) {
											if ($this->cacheTables) {
												$cadj = $this->_uncacheCell($table['cells'][$brow][$ctj], '', $fh);
			 	   								list($bt,$br,$bb,$bl) = $this->_getBorderWidths($cadj['borderbin']);
											}
											else if ($this->packTableData) {
			 	   								list($bt,$br,$bb,$bl) = $this->_getBorderWidths($cells[$brow][$ctj]['borderbin']);
											}
											else {
												$bb = $cells[$brow][$ctj]['border_details']['B']['w'];
											}
											$maxbwbottom = max($maxbwbottom , $bb);
										}
									   }
									}
									if (!empty($tableheader)) { $maxbwtop = $table['max_cell_border_width']['T']; }
									else {
									   $trow = $i-1;
									   for( $ctj = 0 ; $ctj < $numcols ; $ctj++ ) {
										if (isset($cells[$trow][$ctj]) && $cells[$trow][$ctj]) {
											if ($this->cacheTables) {
												$cadj = $this->_uncacheCell($table['cells'][$trow][$ctj], '', $fh);
			 	   								list($bt,$br,$bb,$bl) = $this->_getBorderWidths($cadj['borderbin']);
											}
											else if ($this->packTableData) {
			 	   								list($bt,$br,$bb,$bl) = $this->_getBorderWidths($cells[$trow][$ctj]['borderbin']);
											}
											else {
												$bt = $cells[$trow][$ctj]['border_details']['T']['w'];
											}
											$maxbwtop = max($maxbwtop , $bt);
										}
									   }
									}
								}
								else if ($this->simpleTables){
									$maxbwtop = $table['simple']['border_details']['T']['w'];
									$maxbwbottom = $table['simple']['border_details']['B']['w'];
								}
								$adv = $maxbwbottom /2;
							}
							$this->y += $adv;
						}

						// Rotated table split over pages - needs this->y for borders/backgrounds
						if($i>0 && $this->table_rotate && $level==1) {
				//			$this->y = $y0 + $this->tbrot_w;
						}

						if ($this->tableClipPath ) { $this->_out("Q"); }

						$bx = $x0;
						$by = $y0;

						if ($table['borders_separate']) {
							$bx -= ($table['padding']['L'] + $table['border_details']['L']['w'] + $table['border_spacing_H']/2);
							if ($tablestartpageno != $this->page) {	// IF already broken across a previous pagebreak
								$by += $table['max_cell_border_width']['T']/2;
								if (empty($tableheader)) { $by -= ($table['border_spacing_V']/2); }
							}
							else {
								$by -= ($table['padding']['T'] + $table['border_details']['T']['w'] + $table['border_spacing_V']/2);
							}
						}

						else if ($tablestartpageno != $this->page && !empty($tableheader)) { $by += $maxbwtop /2; }
	
						$by -= $tableheaderadj;
						$bh = $this->y - $by + $tablefooteradj;
  						if (!$table['borders_separate']) { $bh -= $adv ; }
						if ($split) {
							$bw = 0;
							for($t=$startcol; $t<$numcols; $t++) {
								if ($table['colPg'][$t] == $splitpg) { $bw += $table['wc'][$t]; }
								if ($table['colPg'][$t] > $splitpg) { break; }
							}
							if ($table['borders_separate']) {
								if ($firstSpread) {
									$bw += $table['padding']['L'] + $table['border_details']['L']['w'] + $table['border_spacing_H'];
								}
								else {
									$bx += ($table['padding']['L'] + $table['border_details']['L']['w']);
									$bw += $table['border_spacing_H'];
								}
								if ($finalSpread) {
									$bw += $table['padding']['R'] + $table['border_details']['R']['w']/2 + $table['border_spacing_H'];
								}
							}
						}
						else {
							$bw = $table['w'] - ($table['max_cell_border_width']['L']/2) - ($table['max_cell_border_width']['R']/2) - $table['margin']['L'] - $table['margin']['R'];
						}

						// mPDF 5.4.16
		      			if ($this->splitTableBorderWidth && ($this->keepColumns || !$this->ColActive) && empty($tablefooter) && $i > 0 && $table['border_details']['B']['w']) {
							$prevDrawColor = $this->DrawColor;
							$lw = $this->LineWidth;
							$this->SetLineWidth($this->splitTableBorderWidth);
							$this->SetDColor($table['border_details']['B']['c']);
							$this->SetLineJoin(0);
							$this->SetLineCap(0);
 							$blx = $bx;
							$blw = $bw;
 							if (!$table['borders_separate']) {
								$blx -= ($table['max_cell_border_width']['L']/2);
								$blw += ($table['max_cell_border_width']['L']/2 + $table['max_cell_border_width']['R']/2);
							}
							$this->Line($blx,$this->y+($this->splitTableBorderWidth/2),$blx+$blw,$this->y+($this->splitTableBorderWidth/2));
							$this->DrawColor = $prevDrawColor;
							$this->_out($this->DrawColor);
							$this->SetLineWidth($lw);
							$this->SetLineJoin(2);
							$this->SetLineCap(2);
						}

						if (!$this->ColActive && ($i > 0 || $j > 0)) {
							if (isset($table['bgcolor'][-1])) {
					  			$color = $this->ConvertColor($table['bgcolor'][-1]);
					  			if ($color) {
								   if (!$table['borders_separate']) { $bh -= $table['max_cell_border_width']['B']/2; }
								   $this->tableBackgrounds[$level*9][] = array('gradient'=>false, 'x'=>$bx, 'y'=>$by, 'w'=>$bw, 'h'=>$bh, 'col'=>$color);
								}
							}

						}

						// $this->AcceptPageBreak() has moved tablebuffer to $this->pages content
						if ($this->tableBackgrounds) {
						   $s = $this->PrintTableBackgrounds();
	   					   if ($this->bufferoutput) {
							$this->headerbuffer = preg_replace('/(___TABLE___BACKGROUNDS'.date('jY').')/', '\\1'."\n".$s."\n", $this->headerbuffer);
							$this->headerbuffer = preg_replace('/(___TABLE___BACKGROUNDS'.date('jY').')/', " ", $this->headerbuffer );
						   }
						   else {
							$this->pages[$this->page] = preg_replace('/(___TABLE___BACKGROUNDS'.date('jY').')/', '\\1'."\n".$s."\n", $this->pages[$this->page]);
							$this->pages[$this->page] = preg_replace('/(___TABLE___BACKGROUNDS'.date('jY').')/', " ", $this->pages[$this->page]);
						   }
						   $this->tableBackgrounds = array();
						}

						if ($split) {
							if ($i == 0 && $j == 0) { $y0 = -1; }
							else if ($finalSpread) {
								$splitpg = 0;
								$startcol = 0;
								$startrow = $i;
							}
							else {
								$splitpg++;
								$startcol = $t;
								$returny -= $y;
							}
							return array(false, $startrow, $startcol, $splitpg, $returny, $y0);
						}

						$this->AddPage($this->CurOrientation);

						$this->_out('___TABLE___BACKGROUNDS'.date('jY'));


						if ($this->tableClipPath ) { $this->_out($this->tableClipPath); }

						// Added to correct for OddEven Margins
						$x= $x + $this->MarginCorrection;
						$x0= $x0 + $this->MarginCorrection;

						// mPDF 5.4.16
		      			if ($this->splitTableBorderWidth && ($this->keepColumns || !$this->ColActive) && empty($tableheader) && $i > 0 && $table['border_details']['T']['w'] ) {
							$prevDrawColor = $this->DrawColor;
							$lw = $this->LineWidth;
							$this->SetLineWidth($this->splitTableBorderWidth);
							$this->SetDColor($table['border_details']['T']['c']);
							$this->SetLineJoin(0);
							$this->SetLineCap(0);
							$blx += $this->MarginCorrection;
							$this->Line($blx,$this->y-($this->splitTableBorderWidth/2),$blx+$blw,$this->y-($this->splitTableBorderWidth/2));
							$this->DrawColor = $prevDrawColor;
							$this->_out($this->DrawColor);
							$this->SetLineWidth($lw);
							$this->SetLineJoin(2);
							$this->SetLineCap(2);
						}

						// Move down to account for half of top border-spacing or
						// extra half border width in case page was broken in middle
						if($i>0 && !$this->table_rotate && $level==1 && $table['headernrows']==0) {
							if ($table['borders_separate']) { $adv = $table['border_spacing_V']/2; }
							else {
								$maxbwtop = 0;
								for( $ctj = 0 ; $ctj < $numcols ; $ctj++ ) {
									if (isset($cells[$i][$ctj]) && $cells[$i][$ctj]) {
										if (!$this->simpleTables){
											if ($this->cacheTables) {
												$celltj = $this->_uncacheCell($table['cells'][$i][$ctj], '', $fh);
			 	   								list($bt,$br,$bb,$bl) = $this->_getBorderWidths($celltj['borderbin']);
											}
			 								else if ($this->packTableData) {
			 	   								list($bt,$br,$bb,$bl) = $this->_getBorderWidths($cells[$i][$ctj]['borderbin']);
											}
											else {
												$bt = $cells[$i][$ctj]['border_details']['T']['w'];
											}
											$maxbwtop = max($maxbwtop, $bt);
										}
										else if ($this->simpleTables){
											$maxbwtop = max($maxbwtop, $table['simple']['border_details']['T']['w']);
										}
									}
								}
								$adv = $maxbwtop /2;
							}
							$this->y += $adv;
						}


						if ($this->table_rotate) {
							$this->tbrot_x0 = $this->lMargin  + $this->blk[$this->blklvl]['outer_left_margin'] + $this->blk[$this->blklvl]['padding_left'] + $this->blk[$this->blklvl]['border_left']['w'];
							if ($table['borders_separate']) { $this->tbrot_h = $table['margin']['T'] + $table['padding']['T'] + $table['border_details']['T']['w'] + $table['border_spacing_V']/2; }
							else { $this->tbrot_h = $table['margin']['T'] + $table['max_cell_border_width']['T'] ; }
							$this->tbrot_y0 = $this->y;
							$pagetrigger = $y0 - $tableheaderadj + ($this->blk[$this->blklvl]['inner_width']);
						}
						else {
							$pagetrigger = $this->PageBreakTrigger;
						}

						if ($this->kwt_saved && $level==1) {
							$this->kwt_moved = true;
						}


						// Disable Table header repeat if Keep Block together
             				if (!$this->keep_block_together && !empty($tableheader)) {
							$ya = $this->y;
							$this->TableHeaderFooter($tableheader,$tablestartpage,$tablestartcolumn,'H',$level);
							if ($this->table_rotate) {
								$this->tbrot_h = $this->y - $ya ;
							}
							$tableheaderadj = $this->y - $ya ;
						}

						else if ($i==0 && !$this->keep_block_together && !$this->table_rotate && $level==1 && !$this->ColActive) {
							// Advance down page
							if ($table['borders_separate']) { $adv = $table['border_spacing_V']/2 + $table['border_details']['T']['w'] + $table['padding']['T'];  }
							else { $adv = $table['max_cell_border_width']['T'] /2 ; }
							if ($adv) {
							   if ($this->table_rotate) {
								$this->y += ($adv);
							   }
							   else {
								$this->DivLn($adv,$this->blklvl,true);
							   }
							}
						}

						$outerfilled = 0;
						$y = $y0 = $this->y;
					}

				}
				$skippage = true;
			}

			$this->x = $x;
			$this->y = $y;

			if ($this->kwt_saved && $level==1) {
				$this->printkwtbuffer();
				$x0 = $x = $this->x;
				$y0 = $y = $this->y;
				$this->kwt_moved = false;
				$this->kwt_saved = false;
			}


			// Set the Page & Column where table actually starts
			if ($i==0 && $j==0 && $level==1) {
				if (($this->mirrorMargins) && (($this->page)%2==0)) {				// EVEN
					$tablestartpage = 'EVEN';
				}
				else if (($this->mirrorMargins) && (($this->page)%2==1)) {				// ODD
					$tablestartpage = 'ODD';
				}
				else { $tablestartpage = ''; }
				$tablestartpageno = $this->page;
			}


			//ALIGN
			$align = $cell['a'];



			if ($table['empty_cells']!='hide' || !empty($cell['textbuffer']) || (isset($cell['nestedcontent']) && $cell['nestedcontent']) || !$table['borders_separate']  ) { $paintcell = true; }
			else { $paintcell = false; }

			//Set Borders
			$bord = 0;
			$bord_det = array();

			if (!$this->simpleTables){
			 	if ($this->packTableData) {
	  			   if ($cell['borderbin']) {
					$c = $this->_unpackCellBorder($cell['borderbin']);
					$bord = $c['border'];
					$bord_det = $c['border_details'];
				   }
				}
				else if ($cell['border']) {
					$bord = $cell['border'];
					$bord_det = $cell['border_details'];
				}
			}
			else if ($this->simpleTables){
	  			if ($table['simple']['border']) {
					$bord = $table['simple']['border'];
					$bord_det = $table['simple']['border_details'];
				}
			}

			//TABLE ROW OR CELL FILL BGCOLOR
			$fill = 0;
			if (isset($cell['bgcolor']) && $cell['bgcolor'] && $cell['bgcolor']!='transparent') {
				$fill = $cell['bgcolor'];
				$leveladj = 6;
			}
			else if (isset($table['bgcolor'][$i]) && $table['bgcolor'][$i] && $table['bgcolor'][$i]!='transparent') { // Row color
				$fill = $table['bgcolor'][$i];
				$leveladj = 3;
			}
			if ($fill && $paintcell) {
  				$color = $this->ConvertColor($fill);
  				if ($color) {
 					if ($table['borders_separate']) {
					   if ($this->ColActive) {
						$this->SetFColor($color);
						$this->Rect($x+ ($table['border_spacing_H']/2), $y+ ($table['border_spacing_V']/2), $w- $table['border_spacing_H'], $h- $table['border_spacing_V'], 'F');
					   }
					   else {
		   				$this->tableBackgrounds[$level*9+$leveladj][] = array('gradient'=>false, 'x'=>($x + ($table['border_spacing_H']/2)), 'y'=>($y + ($table['border_spacing_V']/2)), 'w'=>($w - $table['border_spacing_H']), 'h'=>($h - $table['border_spacing_V']), 'col'=>$color);
					   }
					}
 					else {
					   if ($this->ColActive) {
						$this->SetFColor($color);
	 					$this->Rect($x, $y, $w, $h, 'F');
					   }
					   else {
		   				$this->tableBackgrounds[$level*9+$leveladj][] = array('gradient'=>false, 'x'=>$x, 'y'=>$y, 'w'=>$w, 'h'=>$h, 'col'=>$color);
					   }
					}
				}
			}


			 if (isset($cell['colspan']) && $cell['colspan']>1) { $ccolsp = $cell['colspan']; }
			 else { $ccolsp = 1; }
			 if (isset($cell['rowspan']) && $cell['rowspan']>1) { $crowsp = $cell['rowspan']; }
			 else { $crowsp = 1; }


			// but still need to do this for repeated headers...
			if (!$table['borders_separate'] && $this->tabletheadjustfinished && !$this->simpleTables){
			  if (isset($table['topntail']) && $table['topntail']) {
					$bord_det['T'] = $this->border_details($table['topntail']);
					$bord_det['T']['w'] /= $this->shrin_k;
					$this->setBorder($bord, _BORDER_TOP);
			  }
			  if (isset($table['thead-underline']) && $table['thead-underline']) {
					$bord_det['T'] = $this->border_details($table['thead-underline']);
					$bord_det['T']['w'] /= $this->shrin_k;
					$this->setBorder($bord, _BORDER_TOP);
			  }
			}


			//Get info of first row ==>> table header
			//Use > 1 row if THEAD
			if (isset($table['is_thead'][$i]) && $table['is_thead'][$i] && $level==1) {
				if ($j==0) $tableheaderrowheight += $table['hr'][$i];
				$tableheader[$i][0]['trbackground-images'] = (isset($table['trbackground-images'][$i]) ? $table['trbackground-images'][$i] : null);
				$tableheader[$i][0]['trgradients'] = (isset($table['trgradients'][$i]) ? $table['trgradients'][$i] : null);
				$tableheader[$i][0]['trbgcolor'] = (isset($table['bgcolor'][$i]) ? $table['bgcolor'][$i] : null);
				$tableheader[$i][$j]['x'] = $x;
				$tableheader[$i][$j]['y'] = $y;
				$tableheader[$i][$j]['h'] = $h;
				$tableheader[$i][$j]['w'] = $w;
				if (isset($cell['textbuffer'])) { $tableheader[$i][$j]['textbuffer'] = $cell['textbuffer']; }
				else { $tableheader[$i][$j]['textbuffer'] = ''; }
				$tableheader[$i][$j]['a'] = $cell['a'];
				$tableheader[$i][$j]['R'] = $cell['R'];

				$tableheader[$i][$j]['va'] = $cell['va'];
				$tableheader[$i][$j]['mih'] = $cell['mih'];
				$tableheader[$i][$j]['rowspan'] = (isset($cell['rowspan']) ? $cell['rowspan'] : null);
				$tableheader[$i][$j]['colspan'] = (isset($cell['colspan']) ? $cell['colspan'] : null);
				$tableheader[$i][$j]['bgcolor'] = $cell['bgcolor'];

				if (!$this->simpleTables){
					$tableheader[$i][$j]['border'] = $bord;
					$tableheader[$i][$j]['border_details'] = $bord_det;
				}
				else if ($this->simpleTables){
					$tableheader[$i][$j]['border'] = $table['simple']['border'];
					$tableheader[$i][$j]['border_details'] = $table['simple']['border_details'];
				}
				$tableheader[$i][$j]['padding'] = $cell['padding'];
			}

			// CELL BORDER
			if ($bord || $bord_det) {
 				if ($table['borders_separate'] && $paintcell) {
 					$this->_tableRect($x + ($table['border_spacing_H']/2)+($bord_det['L']['w'] /2), $y+ ($table['border_spacing_V']/2)+($bord_det['T']['w'] /2), $w-$table['border_spacing_H']-($bord_det['L']['w'] /2)-($bord_det['R']['w'] /2), $h- $table['border_spacing_V']-($bord_det['T']['w'] /2)-($bord_det['B']['w']/2), $bord, $bord_det, false, $table['borders_separate']);
				}
 				else if (!$table['borders_separate']) {
					$this->_tableRect($x, $y, $w, $h, $bord, $bord_det, true, $table['borders_separate']); 	// true causes buffer
				}

			}

			//VERTICAL ALIGN
			if ($cell['R'] && INTVAL($cell['R']) > 0 && INTVAL($cell['R']) < 90 && isset($cell['va']) && $cell['va']!='B') { $cell['va']='B';}
			if (!isset($cell['va']) || $cell['va']=='M') $this->y += ($h-$cell['mih'])/2;
			elseif (isset($cell['va']) && $cell['va']=='B') $this->y += $h-$cell['mih'];

			// NESTED CONTENT

			// TEXT (and nested tables)
			$this->divalign=$align;

			$this->divwidth=$w;
			if (!empty($cell['textbuffer'])) {
				if ($level==1) {
					if (isset($table['is_tfoot'][$i]) && $table['is_tfoot'][$i]) {
						if (preg_match('/{colsum([0-9]*)[_]*}/', $cell['textbuffer'][0][0], $m)) {
							$rep = sprintf("%01.".intval($m[1])."f", $this->colsums[$j]);
							$cell['textbuffer'][0][0] = preg_replace('/{colsum[0-9_]*}/', $rep ,$cell['textbuffer'][0][0]);
						}
					}
					else { $this->colsums[$j] += floatval(preg_replace('/^[^0-9\.\,]*/','',$cell['textbuffer'][0][0])); }
				}
				$opy = $this->y;
				// mPDF ITERATION
				if ($this->iterationCounter) {
				   foreach($cell['textbuffer'] AS $k=>$t) {
					if (preg_match('/{iteration ([a-zA-Z0-9_]+)}/',$t[0], $m)) {
						$vname = '__'.$m[1].'_';
						if (!isset($this->$vname)) { $this->$vname = 1; }
						else { $this->$vname++; }
						$cell['textbuffer'][$k][0] = preg_replace('/{iteration '.$m[1].'}/', $this->$vname, $cell['textbuffer'][$k][0]);
					}
				   }
				}


				if ($cell['R']) {
					$cellPtSize = $cell['textbuffer'][0][11] / $this->shrin_k;
					if (!$cellPtSize) { $cellPtSize = $this->default_font_size; }
					$cellFontHeight = ($cellPtSize/_MPDFK);
					$opx = $this->x;
					$angle = INTVAL($cell['R']);
					// Only allow 45 to 89 degrees (when bottom-aligned) or exactly 90 or -90
					if ($angle > 90) { $angle = 90; }
					else if ($angle > 0 && $angle <45) { $angle = 45; }
					else if ($angle < 0) { $angle = -90; }
					$offset = ((sin(deg2rad($angle))) * 0.37 * $cellFontHeight);
					if (isset($cell['a']) && $cell['a']=='R') {
						$this->x += ($w) + ($offset) - ($cellFontHeight/3) - ($cell['padding']['R'] + ($table['border_spacing_H']/2));
					}
					else if (!isset($cell['a']) || $cell['a']=='C') {
						$this->x += ($w/2) + ($offset);
					}
					else {
						$this->x += ($offset) + ($cellFontHeight/3)+($cell['padding']['L'] +($table['border_spacing_H']/2));
					}
					$str = '';
					foreach($cell['textbuffer'] AS $t) { $str .= $t[0].' '; }
					$str = trim($str);
					if (!isset($cell['va']) || $cell['va']=='M') {
						$this->y -= ($h-$cell['mih'])/2; //Undo what was added earlier VERTICAL ALIGN
						if ($angle > 0) { $this->y += (($h-$cell['mih'])/2) + $cell['padding']['T'] + ($cell['mih']-($cell['padding']['T'] + $cell['padding']['B'])); }
						else if ($angle < 0) { $this->y += (($h-$cell['mih'])/2)+ ($cell['padding']['T'] + ($table['border_spacing_V']/2)); }
					}
					elseif (isset($cell['va']) && $cell['va']=='B') {
						$this->y -= $h-$cell['mih']; //Undo what was added earlier VERTICAL ALIGN
						if ($angle > 0) { $this->y += $h-($cell['padding']['B'] + ($table['border_spacing_V']/2)); }
						else if ($angle < 0) { $this->y += $h-$cell['mih'] + ($cell['padding']['T'] + ($table['border_spacing_V']/2)); }
					}
					elseif (isset($cell['va']) && $cell['va']=='T') {
						if ($angle > 0) { $this->y += $cell['mih']-($cell['padding']['B'] + ($table['border_spacing_V']/2)); }
						else if ($angle < 0) { $this->y += ($cell['padding']['T'] + ($table['border_spacing_V']/2)); }
					}
					$this->Rotate($angle,$this->x,$this->y);
					$s_fs = $this->FontSizePt;
					$s_f = $this->FontFamily;
					$s_st = $this->FontStyle;
					if (!empty($cell['textbuffer'][0][3])) { //Font Color
						$cor = $cell['textbuffer'][0][3];
						$this->SetTColor($cor);
					}
					$s_str = $this->strike;
					$this->strike = $cell['textbuffer'][0][8]; //Strikethrough
					$this->SetFont($cell['textbuffer'][0][4],$cell['textbuffer'][0][2],$cellPtSize,true,true);
					$this->Text($this->x,$this->y,$str);
					$this->Rotate(0);
					$this->SetFont($s_f,$s_st,$s_fs,true,true);
					$this->SetTColor(0);
					$this->strike = $s_str;
					$this->x = $opx;
				}
				else {

					if (!$this->simpleTables){
					   if ($bord_det) {
						$btlw = $bord_det['L']['w'];
						$btrw = $bord_det['R']['w'];
						$bttw = $bord_det['T']['w'];
					   }
					   else {
						$btlw = 0;
						$btrw = 0;
						$bttw = 0;
					   }
					   if ($table['borders_separate']) {
						$xadj = $btlw + $cell['padding']['L'] +($table['border_spacing_H']/2);
						$wadj = $btlw + $btrw + $cell['padding']['L'] +$cell['padding']['R'] + $table['border_spacing_H'];
						$yadj = $bttw + $cell['padding']['T'] + ($table['border_spacing_H']/2);
					   }
					   else {
						$xadj = $btlw/2 + $cell['padding']['L'];
						$wadj = ($btlw + $btrw)/2 + $cell['padding']['L'] + $cell['padding']['R'];
						$yadj = $bttw/2 + $cell['padding']['T'];
					   }
					}
					else if ($this->simpleTables){
					   if ($table['borders_separate']) {	// NB twice border width
						$xadj = $table['simple']['border_details']['L']['w'] + $cell['padding']['L'] +($table['border_spacing_H']/2);
						$wadj = $table['simple']['border_details']['L']['w'] + $table['simple']['border_details']['R']['w'] + $cell['padding']['L'] +$cell['padding']['R'] + $table['border_spacing_H'];
						$yadj = $table['simple']['border_details']['T']['w'] + $cell['padding']['T'] + ($table['border_spacing_H']/2);
					   }
					   else {
						$xadj = $table['simple']['border_details']['L']['w']/2 + $cell['padding']['L'];
						$wadj = ($table['simple']['border_details']['L']['w'] + $table['simple']['border_details']['R']['w'])/2 + $cell['padding']['L'] + $cell['padding']['R'];
						$yadj = $table['simple']['border_details']['T']['w']/2 + $cell['padding']['T'];
					   }
					}

					$this->divwidth=$w-$wadj;
					if ($this->divwidth == 0) { $this->divwidth = 0.0001; }
					$this->x += $xadj;
					$this->y += $yadj;
					$this->printbuffer($cell['textbuffer'],'',true);
				}
				$this->y = $opy;
			}


			// TABLE BORDER - if separate
			if (($table['borders_separate'] || ($this->simpleTables && !$table['simple']['border'])) && $table['border']) {
			   $halfspaceL = $table['padding']['L'] + ($table['border_spacing_H']/2);
			   $halfspaceR = $table['padding']['R'] + ($table['border_spacing_H']/2);
			   $halfspaceT = $table['padding']['T'] + ($table['border_spacing_V']/2);
			   $halfspaceB = $table['padding']['B'] + ($table['border_spacing_V']/2);
			   $tbx = $x;
			   $tby = $y;
			   $tbw = $w;
			   $tbh = $h;
			   $tab_bord = 0;
			   
			   $corner = '';
 			   if ($i == 0) {		// Top
				$tby -= $halfspaceT + ($table['border_details']['T']['w']/2);
				$tbh += $halfspaceT + ($table['border_details']['T']['w']/2);
				$this->setBorder($tab_bord , _BORDER_TOP);
				$corner .= 'T';
			   }
			   if ($i == ($numrows-1) || (isset($cell['rowspan']) && ($i+$cell['rowspan']) == $numrows)) {	// Bottom
				$tbh += $halfspaceB + ($table['border_details']['B']['w']/2);
				$this->setBorder($tab_bord , _BORDER_BOTTOM);
				$corner .= 'B';
			   }
			   if ($j == 0) {		// Left
				$tbx -= $halfspaceL + ($table['border_details']['L']['w']/2);
				$tbw += $halfspaceL + ($table['border_details']['L']['w']/2);
				$this->setBorder($tab_bord , _BORDER_LEFT);
				$corner .= 'L';
			   }
			   if ($j == ($numcols-1) || (isset($cell['colspan']) && ($j+$cell['colspan']) == $numcols)) {	// Right
				$tbw += $halfspaceR + ($table['border_details']['R']['w']/2);
				$this->setBorder($tab_bord , _BORDER_RIGHT);
				$corner .= 'R';
			   }
			   $this->_tableRect($tbx, $tby, $tbw, $tbh, $tab_bord , $table['border_details'], false, $table['borders_separate'], 'table', $corner, $table['border_spacing_V'], $table['border_spacing_H'] );
			}

			unset($cell );
			//Reset values
			$this->Reset();

		}//end of (if isset(cells)...)
	  }// end of columns

	  $newpagestarted = false;
	  $this->tabletheadjustfinished = false;



	  if ($i == $numrows-1) { $this->y = $y + $h; } //last row jump (update this->y position)
	  if ($this->table_rotate && $level==1) {
		$this->tbrot_h += $h;
	  }



	}// end of rows


	if (count($this->cellBorderBuffer)) { $this->printcellbuffer(); }

 
	if ($this->tableClipPath ) { $this->_out("Q"); }
	$this->tableClipPath = '';

	// Advance down page by half width of bottom border
 	if ($table['borders_separate']) { $this->y += $table['padding']['B'] + $table['border_details']['B']['w'] + $table['border_spacing_V']/2; }
	else { $this->y += $table['max_cell_border_width']['B']/2; }

	if ($table['borders_separate'] && $level==1) { $this->tbrot_h += $table['margin']['B'] + $table['padding']['B'] + $table['border_details']['B']['w'] + $table['border_spacing_V']/2; }
	else if ($level==1) { $this->tbrot_h += $table['margin']['B'] + $table['max_cell_border_width']['B']/2; }

	$bx = $x0;
	$by = $y0;
	if ($table['borders_separate']) {
		$bx -= ($table['padding']['L'] + $table['border_details']['L']['w'] + $table['border_spacing_H']/2);
		if ($tablestartpageno != $this->page) {	// IF broken across page
			$by += $table['max_cell_border_width']['T']/2;
			if (empty($tableheader)) { $by -= ($table['border_spacing_V']/2); }
		}
		else if ($split && $startrow > 0 && empty($tableheader)) {
			$by -= ($table['border_spacing_V']/2);
		}
		else {
			$by -= ($table['padding']['T'] + $table['border_details']['T']['w'] + $table['border_spacing_V']/2);
		}
	}
	else if ($tablestartpageno != $this->page && !empty($tableheader)) { $by += $maxbwtop /2; }
	$by -= $tableheaderadj;
	$bh = $this->y - $by;
	if (!$table['borders_separate']) { $bh -= $table['max_cell_border_width']['B']/2; }

	if ($split) {
		$bw = 0;
		$finalSpread = true;
		for($t=$startcol; $t<$numcols; $t++) {
			if ($table['colPg'][$t] == $splitpg) { $bw += $table['wc'][$t]; }
			if ($table['colPg'][$t] > $splitpg) { $finalSpread = false; break; }
		}
		if ($startcol==0) { $firstSpread = true; }
		else { $firstSpread = false; }
		if ($table['borders_separate']) {
			$bw += $table['border_spacing_H'];
			if ($firstSpread) {
				$bw += $table['padding']['L'] + $table['border_details']['L']['w'];
			}
			else {
				$bx += ($table['padding']['L'] + $table['border_details']['L']['w']);
			}
			if ($finalSpread) {
				$bw += $table['padding']['R'] + $table['border_details']['R']['w'];
			}
		}
	}
	else {
		$bw = $table['w'] - ($table['max_cell_border_width']['L']/2) - ($table['max_cell_border_width']['R']/2) - $table['margin']['L'] - $table['margin']['R'];
	}

	if (!$this->ColActive) {
		if (isset($table['bgcolor'][-1])) {
  			$color = $this->ConvertColor($table['bgcolor'][-1]);
  			if ($color) {
			   $this->tableBackgrounds[$level*9][] = array('gradient'=>false, 'x'=>$bx, 'y'=>$by, 'w'=>$bw, 'h'=>$bh, 'col'=>$color);
			}
		}

	}

	if ($this->tableBackgrounds && $level == 1) {
	   $s = $this->PrintTableBackgrounds();
	   if ($this->table_rotate && !$this->processingHeader && !$this->processingFooter) {
		$this->tablebuffer = preg_replace('/(___TABLE___BACKGROUNDS'.date('jY').')/', '\\1'."\n".$s."\n", $this->tablebuffer);
		if ($level == 1) { $this->tablebuffer = preg_replace('/(___TABLE___BACKGROUNDS'.date('jY').')/', " ", $this->tablebuffer); }
	   }
	   else if ($this->bufferoutput) {
		$this->headerbuffer = preg_replace('/(___TABLE___BACKGROUNDS'.date('jY').')/', '\\1'."\n".$s."\n", $this->headerbuffer);
		if ($level == 1) { $this->headerbuffer = preg_replace('/(___TABLE___BACKGROUNDS'.date('jY').')/', " ", $this->headerbuffer ); }
	   }
	   else {
		$this->pages[$this->page] = preg_replace('/(___TABLE___BACKGROUNDS'.date('jY').')/', '\\1'."\n".$s."\n", $this->pages[$this->page]);
		if ($level == 1) { $this->pages[$this->page] = preg_replace('/(___TABLE___BACKGROUNDS'.date('jY').')/', " ", $this->pages[$this->page]); }
	   }
	   $this->tableBackgrounds = array();
	}


	// TABLE BOTTOM MARGIN
	if ($table['margin']['B']) {
	  if (!$this->table_rotate && $level==1) {
		$this->DivLn($table['margin']['B'],$this->blklvl,true); 	// collapsible
	  }
	  else {
		$this->y += ($table['margin']['B']);
	  }
	}


	if ($this->cacheTables) { fclose($fh); }

	if ($split) {
		// Are there more columns to print on a next page?
		if ($lastCol < $numcols-1) {
			$splitpg++;
			$startcol = $lastCol + 1;
			return array(false, $startrow, $startcol, $splitpg, $returny, $y0);
		}
		else {
			if ($this->cacheTables) {
				unlink($table['cache']);
				@unlink($table['cache'].'.bak');
			}
			return array(true,0,0,0);
		}
	}
	if ($this->cacheTables) {
		unlink($table['cache']);
		@unlink($table['cache'].'.bak');
	}

}//END OF FUNCTION _tableWrite()


/////////////////////////END OF TABLE CODE//////////////////////////////////

function _putextgstates() {
	for ($i = 1; $i <= count($this->extgstates); $i++) {
            $this->_newobj();
            $this->extgstates[$i]['n'] = $this->n;
            $this->_out('<</Type /ExtGState');
            foreach ($this->extgstates[$i]['parms'] as $k=>$v)
                $this->_out('/'.$k.' '.$v);
            $this->_out('>>');
            $this->_out('endobj');
	}
}

function _putocg() {
	if ($this->hasOC) { 	// mPDF 5.6.01
		$this->_newobj();
		$this->n_ocg_print=$this->n;
		$this->_out('<</Type /OCG /Name '.$this->_textstring('Print only'));
		$this->_out('/Usage <</Print <</PrintState /ON>> /View <</ViewState /OFF>>>>>>');
		$this->_out('endobj');
		$this->_newobj();
		$this->n_ocg_view=$this->n;
		$this->_out('<</Type /OCG /Name '.$this->_textstring('Screen only'));
		$this->_out('/Usage <</Print <</PrintState /OFF>> /View <</ViewState /ON>>>>>>');
		$this->_out('endobj');
		$this->_newobj();
		$this->n_ocg_hidden=$this->n;
		$this->_out('<</Type /OCG /Name '.$this->_textstring('Hidden'));
		$this->_out('/Usage <</Print <</PrintState /OFF>> /View <</ViewState /OFF>>>>>>');
		$this->_out('endobj');
	}
	// mPDF 5.6.01  Add Layers
	if (count($this->layers)) {
		ksort($this->layers);
		foreach($this->layers as $id=>$layer) {
			$this->_newobj();
			$this->layers[$id]['n'] = $this->n;
			$this->_out('<</Type /OCG /Name '.$this->_UTF16BEtextstring($layer['name']).'>>');
			$this->_out('endobj');
		}
	}
}




function _putpatterns() {
	for ($i = 1; $i <= count($this->patterns); $i++) {
		$x = $this->patterns[$i]['x'];
		$y = $this->patterns[$i]['y'];
		$w = $this->patterns[$i]['w'];
		$h = $this->patterns[$i]['h'];
		$pgh = $this->patterns[$i]['pgh'];
		$orig_w = $this->patterns[$i]['orig_w'];
		$orig_h = $this->patterns[$i]['orig_h'];
		$image_id = $this->patterns[$i]['image_id'];
		$itype = $this->patterns[$i]['itype'];

		if ($this->patterns[$i]['x_repeat']) { $x_repeat = true; }
		else { $x_repeat = false; }
		if ($this->patterns[$i]['y_repeat']) { $y_repeat = true; }
		else { $y_repeat = false; }
		$x_pos = $this->patterns[$i]['x_pos'];
		if (stristr($x_pos ,'%') ) {
			$x_pos += 0;
			$x_pos /= 100;
			$x_pos = ($w * $x_pos) - ($orig_w/_MPDFK * $x_pos);
		}
		$y_pos = $this->patterns[$i]['y_pos'];
		if (stristr($y_pos ,'%') ) {
			$y_pos += 0;
			$y_pos /= 100;
			$y_pos = ($h * $y_pos) - ($orig_h/_MPDFK * $y_pos);
		}
		$adj_x = ($x_pos + $x) *_MPDFK;
		$adj_y = (($pgh - $y_pos - $y)*_MPDFK) - $orig_h ;
		$img_obj = false;
		if ($itype == 'svg' || $itype == 'wmf') {
			foreach($this->formobjects AS $fo) {
				if ($fo['i'] == $image_id) {
					$img_obj = $fo['n'];
					$fo_w = $fo['w'];
					$fo_h = -$fo['h'];
					$wmf_x = $fo['x'];
					$wmf_y = $fo['y'];
					break;
				}
			}
		}
		else {
			foreach($this->images AS $img) {
				if ($img['i'] == $image_id) { $img_obj = $img['n']; break; }
			}
		}
		if (!$img_obj ) { echo "Problem: Image object not found for background pattern ".$img['i']; exit; }

            $this->_newobj();
            $this->_out('<</ProcSet [/PDF /Text /ImageB /ImageC /ImageI]');
		if ($itype == 'svg' || $itype == 'wmf') {
			$this->_out('/XObject <</FO'.$image_id.' '.$img_obj.' 0 R >>');
			// ******* ADD ANY ExtGStates, Shading AND Fonts needed for the FormObject
			// Set in classes/svg array['fo'] = true
			// Required that _putshaders comes before _putpatterns in _putresources
			// This adds any resources associated with any FormObject to every Formobject - overkill but works!
			if (count($this->extgstates)) {
				$this->_out('/ExtGState <<');
				foreach($this->extgstates as $k=>$extgstate)
				   if (isset($extgstate['fo']) && $extgstate['fo']) {
					if (isset($extgstate['trans']))  $this->_out('/'.$extgstate['trans'].' '.$extgstate['n'].' 0 R');
					else $this->_out('/GS'.$k.' '.$extgstate['n'].' 0 R');
				   }
				$this->_out('>>');
			}
			$this->_out('/Font <<');
			foreach($this->fonts as $font) {
				if (!$font['used'] && $font['type']=='TTF') { continue; }
				if (isset($font['fo']) && $font['fo']) {
				   if ($font['type']=='TTF' && ($font['sip'] || $font['smp'])) {
					foreach($font['n'] AS $k => $fid) {
						$this->_out('/F'.$font['subsetfontids'][$k].' '.$font['n'][$k].' 0 R');
					}
				   }
				   else {
					$this->_out('/F'.$font['i'].' '.$font['n'].' 0 R');
				   }
				}
			}
			$this->_out('>>');
		}
		else {
            	$this->_out('/XObject <</I'.$image_id.' '.$img_obj.' 0 R >>');
		}
            $this->_out('>>');
            $this->_out('endobj');

		$this->_newobj();
		$this->patterns[$i]['n'] = $this->n;
		$this->_out('<< /Type /Pattern /PatternType 1 /PaintType 1 /TilingType 2');
		$this->_out('/Resources '. ($this->n-1) .' 0 R');

		$this->_out(sprintf('/BBox [0 0 %.3F %.3F]',$orig_w,$orig_h));
		if ($x_repeat) { $this->_out(sprintf('/XStep %.3F',$orig_w)); }
		else { $this->_out(sprintf('/XStep %d',99999)); }
		if ($y_repeat) { $this->_out(sprintf('/YStep %.3F',$orig_h)); }
		else { $this->_out(sprintf('/YStep %d',99999)); }

		if ($itype == 'svg' || $itype == 'wmf') {
			$this->_out(sprintf('/Matrix [1 0 0 -1 %.3F %.3F]', $adj_x, ($adj_y+$orig_h)));
			$s = sprintf("q %.3F 0 0 %.3F %.3F %.3F cm /FO%d Do Q",($orig_w/$fo_w), (-$orig_h/$fo_h), -($orig_w/$fo_w)*$wmf_x, ($orig_w/$fo_w)*$wmf_y, $image_id);
		}
		else {
			$this->_out(sprintf('/Matrix [1 0 0 1 %.3F %.3F]',$adj_x,$adj_y));
			$s = sprintf("q %.3F 0 0 %.3F 0 0 cm /I%d Do Q",$orig_w,$orig_h,$image_id);
		}

            if ($this->compress) {
			$this->_out('/Filter /FlateDecode');
			$s = gzcompress($s);
		}
		$this->_out('/Length '.strlen($s).'>>');
		$this->_putstream($s);
		$this->_out('endobj');
	}
}


function _putspotcolors() {
	foreach($this->spotColors as $name=>$color) {
		$this->_newobj();
		$this->_out('[/Separation /'.str_replace(' ','#20',$name));
		$this->_out('/DeviceCMYK <<');
		$this->_out('/Range [0 1 0 1 0 1 0 1] /C0 [0 0 0 0] ');
		$this->_out(sprintf('/C1 [%.3F %.3F %.3F %.3F] ',$color['c']/100,$color['m']/100,$color['y']/100,$color['k']/100));
		$this->_out('/FunctionType 2 /Domain [0 1] /N 1>>]');
		$this->_out('endobj');
		$this->spotColors[$name]['n']=$this->n;
	}
}


function _putresources() {
	if ($this->hasOC || count($this->layers)) 	// mPDF 5.6.01
		$this->_putocg();
	$this->_putextgstates();
	$this->_putspotcolors();
	$this->_putfonts();
	$this->_putimages();
	$this->_putformobjects();	// *IMAGES-CORE*




	//Resource dictionary
	$this->offsets[2]=strlen($this->buffer);
	$this->_out('2 0 obj');
	$this->_out('<</ProcSet [/PDF /Text /ImageB /ImageC /ImageI]');

	$this->_out('/Font <<');
	foreach($this->fonts as $font) {
		if (!$font['used'] && $font['type']=='TTF') { continue; }
		if ($font['type']=='TTF' && ($font['sip'] || $font['smp'])) {
			foreach($font['n'] AS $k => $fid) {
				$this->_out('/F'.$font['subsetfontids'][$k].' '.$font['n'][$k].' 0 R');
			}
		}
		else {
			$this->_out('/F'.$font['i'].' '.$font['n'].' 0 R');
		}
	}
	$this->_out('>>');

	if (count($this->spotColors)) {
		$this->_out('/ColorSpace <<');
		foreach($this->spotColors as $color)
			$this->_out('/CS'.$color['i'].' '.$color['n'].' 0 R');
		$this->_out('>>');
	}

	if (count($this->extgstates)) {
		$this->_out('/ExtGState <<');
		foreach($this->extgstates as $k=>$extgstate)
			if (isset($extgstate['trans']))  $this->_out('/'.$extgstate['trans'].' '.$extgstate['n'].' 0 R');
			else $this->_out('/GS'.$k.' '.$extgstate['n'].' 0 R');
		$this->_out('>>');
	}


	if(count($this->images) || count($this->formobjects) || ($this->enableImports && count($this->tpls))) {
		$this->_out('/XObject <<');
		foreach($this->images as $image)
			$this->_out('/I'.$image['i'].' '.$image['n'].' 0 R');
            foreach($this->formobjects as $formobject)
                $this->_out('/FO'.$formobject['i'].' '.$formobject['n'].' 0 R');
		$this->_out('>>');
	}


	// mPDF 5.6.01
	if ($this->hasOC || count($this->layers)) {
		$this->_out('/Properties <<');
		if ($this->hasOC) {
			$this->_out('/OC1 '.$this->n_ocg_print.' 0 R /OC2 '.$this->n_ocg_view.' 0 R /OC3 '.$this->n_ocg_hidden.' 0 R ');
		}
		if (count($this->layers)) {
			foreach($this->layers as $id=>$layer)
				$this->_out('/ZI'.$id.' '.$layer['n'].' 0 R');
		}
		$this->_out('>>');
	}

	$this->_out('>>');
	$this->_out('endobj');	// end resource dictionary


	if (isset($this->js) && $this->js) {
		$this->_putjavascript();
	}

}


function _putjavascript() {
	$this->_newobj();
	$this->n_js = $this->n;
	$this->_out('<<');
	$this->_out('/Names [(EmbeddedJS) '.(1 + $this->n).' 0 R ]');
	$this->_out('>>');
	$this->_out('endobj');

	$this->_newobj();
	$this->_out('<<');
	$this->_out('/S /JavaScript');
	$this->_out('/JS '.$this->_textstring($this->js));
	$this->_out('>>');
	$this->_out('endobj');
}





function _puttrailer() {
	$this->_out('/Size '.($this->n+1));
	$this->_out('/Root '.$this->n.' 0 R');
	$this->_out('/Info '.$this->InfoRoot.' 0 R');
		$uniqid = md5(time() .  $this->buffer);
		$this->_out('/ID [<'.$uniqid.'> <'.$uniqid.'>]');
}


//=========================================



//======================================================


// DEPRACATED but included for backwards compatability
function startPageNums() {
}

//======================================================

//======================================================
function MovePages($target_page, $start_page, $end_page=-1) {
	// move a page/pages EARLIER in the document
		if ($end_page<1) { $end_page = $start_page; }
		$n_toc = $end_page - $start_page + 1;

		// Set/Update PageNumSubstitutions changes before moving anything
		if (count($this->PageNumSubstitutions)) {
			$tp_present = false;
			$sp_present = false;
			$ep_present = false;
			foreach($this->PageNumSubstitutions AS $k=>$v) {
			  if ($this->PageNumSubstitutions[$k]['from']==$target_page) {
				$tp_present = true;
				if ($this->PageNumSubstitutions[$k]['suppress']!='on' && $this->PageNumSubstitutions[$k]['suppress']!=1) {
					$this->PageNumSubstitutions[$k]['suppress']='off';
				}
			  }
			  if ($this->PageNumSubstitutions[$k]['from']==$start_page) {
				$sp_present = true;
				if ($this->PageNumSubstitutions[$k]['suppress']!='on' && $this->PageNumSubstitutions[$k]['suppress']!=1) {
					$this->PageNumSubstitutions[$k]['suppress']='off';
				}
			  }
			  if ($this->PageNumSubstitutions[$k]['from']==($end_page+1)) {
				$ep_present = true;
				if ($this->PageNumSubstitutions[$k]['suppress']!='on' && $this->PageNumSubstitutions[$k]['suppress']!=1) {
					$this->PageNumSubstitutions[$k]['suppress']='off';
				}
			  }
			}

			if (!$tp_present) {
				list($tp_type, $tp_suppress, $tp_reset) = $this->docPageSettings($target_page);
			}
			if (!$sp_present) {
				list($sp_type, $sp_suppress, $sp_reset) = $this->docPageSettings($start_page);
			}
			if (!$ep_present) {
				list($ep_type, $ep_suppress, $ep_reset) = $this->docPageSettings($start_page-1);
			}

		}

		$last = array();
		//store pages
		for($i = $start_page;$i <= $end_page ;$i++)
			$last[]=$this->pages[$i];
		//move pages
		for($i=$start_page - 1;$i>=($target_page);$i--) {
			$this->pages[$i+$n_toc]=$this->pages[$i];
		}
		//Put toc pages at insert point
		for($i = 0;$i < $n_toc;$i++) {
			$this->pages[$target_page + $i]=$last[$i];
		}


		// Update Page Links
		if (count($this->PageLinks)) {
		   $newarr = array();
		   foreach($this->PageLinks as $i=>$o) {
			foreach($this->PageLinks[$i] as $key => $pl) {
				if (strpos($pl[4],'@')===0) {
					$p=substr($pl[4],1);
					if($p>=$start_page && $p<=$end_page) {
						$this->PageLinks[$i][$key][4] = '@'.($p + ($target_page - $start_page));
					}
					else if($p>=$target_page && $p<$start_page) {
						$this->PageLinks[$i][$key][4] = '@'.($p+$n_toc);
					}
				}
			}
			if($i>=$start_page && $i<=$end_page) {
				$newarr[($i + ($target_page - $start_page))] = $this->PageLinks[$i];
			}
			else if($i>=$target_page && $i<$start_page) {
				$newarr[($i + $n_toc)] = $this->PageLinks[$i];
			}
			else {
				$newarr[$i] = $this->PageLinks[$i];
			}
		   }
		   $this->PageLinks = $newarr;
		}

		// OrientationChanges
		if (count($this->OrientationChanges)) {
			$newarr = array();
			foreach($this->OrientationChanges AS $p=>$v) {
				if($p>=$start_page && $p<=$end_page) { $newarr[($p + ($target_page - $start_page))] = $this->OrientationChanges[$p]; }
				else if($p>=$target_page && $p<$start_page) { $newarr[$p+$n_toc] = $this->OrientationChanges[$p]; }
				else { $newarr[$p] = $this->OrientationChanges[$p]; }
			}
			ksort($newarr);
			$this->OrientationChanges = $newarr;
		}

		// Page Dimensions
		if (count($this->pageDim)) {
			$newarr = array();
			foreach($this->pageDim AS $p=>$v) {
				if($p>=$start_page && $p<=$end_page) { $newarr[($p + ($target_page - $start_page))] = $this->pageDim[$p]; }
				else if($p>=$target_page && $p<$start_page) { $newarr[$p+$n_toc] = $this->pageDim[$p]; }
				else { $newarr[$p] = $this->pageDim[$p]; }
			}
			ksort($newarr);
			$this->pageDim = $newarr;
		}

		// HTML Headers & Footers
		if (count($this->saveHTMLHeader)) {
			$newarr = array();
			foreach($this->saveHTMLHeader AS $p=>$v) {
				if($p>=$start_page && $p<=$end_page) { $newarr[($p + ($target_page - $start_page))] = $this->saveHTMLHeader[$p]; }
				else if($p>=$target_page && $p<$start_page) { $newarr[$p+$n_toc] = $this->saveHTMLHeader[$p]; }
				else { $newarr[$p] = $this->saveHTMLHeader[$p]; }
			}
			ksort($newarr);
			$this->saveHTMLHeader = $newarr;
		}
		if (count($this->saveHTMLFooter)) {
			$newarr = array();
			foreach($this->saveHTMLFooter AS $p=>$v) {
				if($p>=$start_page && $p<=$end_page) { $newarr[($p + ($target_page - $start_page))] = $this->saveHTMLFooter[$p]; }
				else if($p>=$target_page && $p<$start_page) { $newarr[$p+$n_toc] = $this->saveHTMLFooter[$p]; }
				else { $newarr[$p] = $this->saveHTMLFooter[$p]; }
			}
			ksort($newarr);
			$this->saveHTMLFooter = $newarr;
		}

		// Update Internal Links
		if (count($this->internallink)) {
		   foreach($this->internallink as $key=>$o) {
			if($o['PAGE']>=$start_page && $o['PAGE']<=$end_page) {
				$this->internallink[$key]['PAGE'] += ($target_page - $start_page);
			}
			else if($o['PAGE']>=$target_page && $o['PAGE']<$start_page) {
				$this->internallink[$key]['PAGE'] += $n_toc;
			}
		   }
		}

		// Update Links
		if (count($this->links)) {
		   foreach($this->links as $key=>$o) {
			if($o[0]>=$start_page && $o[0]<=$end_page) {
				$this->links[$key][0] += ($target_page - $start_page);
			}
			if($o[0]>=$target_page && $o[0]<$start_page) {
				$this->links[$key][0] += $n_toc;
			}
		   }
		}

		// Update Form fields
		if (count($this->form->forms)) {
		   foreach($this->form->forms as $key=>$f) {
			if($f['page']>=$start_page && $f['page']<=$end_page) {
				$this->form->forms[$key]['page'] += ($target_page - $start_page);
			}
			if($f['page']>=$target_page && $f['page']<$start_page) {
				$this->form->forms[$key]['page'] += $n_toc;
			}
		   }
		}


		// Update PageNumSubstitutions
		if (count($this->PageNumSubstitutions)) {
			$newarr = array();
			foreach($this->PageNumSubstitutions AS $k=>$v) {
				if($this->PageNumSubstitutions[$k]['from']>=$start_page && $this->PageNumSubstitutions[$k]['from']<=$end_page) {
					$this->PageNumSubstitutions[$k]['from'] += ($target_page - $start_page);
					$newarr[$this->PageNumSubstitutions[$k]['from']] = $this->PageNumSubstitutions[$k];
				}
				else if($this->PageNumSubstitutions[$k]['from']>=$target_page && $this->PageNumSubstitutions[$k]['from']<$start_page) {
					$this->PageNumSubstitutions[$k]['from'] += $n_toc;
					$newarr[$this->PageNumSubstitutions[$k]['from']] = $this->PageNumSubstitutions[$k];
				}
				else {
					$newarr[$this->PageNumSubstitutions[$k]['from']] = $this->PageNumSubstitutions[$k];
				}
			}

			if (!$sp_present) {
					$newarr[$target_page] = array('from'=>$target_page, 'suppress'=>$sp_suppress, 'reset'=>$sp_reset, 'type'=>$sp_type);
			}
			if (!$tp_present) {
					$newarr[($target_page + $n_toc)] = array('from'=>($target_page+$n_toc), 'suppress'=>$tp_suppress, 'reset'=>$tp_reset, 'type'=>$tp_type);
			}
			if (!$ep_present && $end_page>count($this->pages)) {
					$newarr[($end_page+1)] = array('from'=>($end_page+1), 'suppress'=>$ep_suppress, 'reset'=>$ep_reset, 'type'=>$ep_type);
			}
			ksort($newarr);
			$this->PageNumSubstitutions = array();
			foreach($newarr as $v) {
				$this->PageNumSubstitutions[] = $v;
			}
		}
}

//======================================================
function DeletePages($start_page, $end_page=-1) {
	// move a page/pages EARLIER in the document
		if ($end_page<1) { $end_page = $start_page; }
		$n_tod = $end_page - $start_page + 1;
		$last_page = count($this->pages);
		$n_atend = $last_page - $end_page + 1;

		//move pages
		for($i=0;$i<$n_atend;$i++) {
			$this->pages[$start_page+$i]=$this->pages[$end_page+1+$i];
		}
		//delete pages
		for($i = 0;$i < $n_tod ;$i++)
			unset($this->pages[$last_page-$i]);



		// Update Page Links
		if (count($this->PageLinks)) {
		   $newarr = array();
		   foreach($this->PageLinks as $i=>$o) {
			foreach($this->PageLinks[$i] as $key => $pl) {
				if (strpos($pl[4],'@')===0) {
					$p=substr($pl[4],1);
					if($p>$end_page) { $this->PageLinks[$i][$key][4] = '@'.($p - $n_tod); }
					else if($p<$start_page) { unset($this->PageLinks[$i][$key]); }
				}
			}
			if($i>$end_page) { $newarr[($i - $n_tod)] = $this->PageLinks[$i]; }
			else if($p<$start_page) { $newarr[$i] = $this->PageLinks[$i]; }
		   }
		   $this->PageLinks = $newarr;
		}

		// OrientationChanges
		if (count($this->OrientationChanges)) {
			$newarr = array();
			foreach($this->OrientationChanges AS $p=>$v) {
				if($p>$end_page) { $newarr[($p - $t_tod)] = $this->OrientationChanges[$p]; }
				else if($p<$start_page) { $newarr[$p] = $this->OrientationChanges[$p]; }
			}
			ksort($newarr);
			$this->OrientationChanges = $newarr;
		}

		// Page Dimensions
		if (count($this->pageDim)) {
			$newarr = array();
			foreach($this->pageDim AS $p=>$v) {
				if($p>$end_page) { $newarr[($p - $n_tod)] = $this->pageDim[$p]; }
				else if($p<$start_page) { $newarr[$p] = $this->pageDim[$p]; }
			}
			ksort($newarr);
			$this->pageDim = $newarr;
		}

		// HTML Headers & Footers
		if (count($this->saveHTMLHeader)) {
			foreach($this->saveHTMLHeader AS $p=>$v) {
				if($p>end_page) { $newarr[($p - $n_tod)] = $this->saveHTMLHeader[$p]; }
				else if($p<$start_page) { $newarr[$p] = $this->saveHTMLHeader[$p]; }
			}
			ksort($newarr);
			$this->saveHTMLHeader = $newarr;
		}
		if (count($this->saveHTMLFooter)) {
			$newarr = array();
			foreach($this->saveHTMLFooter AS $p=>$v) {
				if($p>$end_page) { $newarr[($p - $n_tod)] = $this->saveHTMLFooter[$p]; }
				else if($p<$start_page) { $newarr[$p] = $this->saveHTMLFooter[$p]; }
			}
			ksort($newarr);
			$this->saveHTMLFooter = $newarr;
		}

		// Update Internal Links
		foreach($this->internallink as $key=>$o) {
			if($o['PAGE']>$end_page) { $this->internallink[$key]['PAGE'] -= $n_tod; }
			else if($o['PAGE']<$start_page) { unset($this->internallink[$key]); }
		}

		// Update Links
		foreach($this->links as $key=>$o) {
			if($o[0]>$end_page) { $this->links[$key][0] -= $n_tod; }
			else if($o[0]<$start_page) { unset($this->links[$key]); }
		}

		// Update Form fields
		foreach($this->form->forms as $key=>$f) {
			if($f['page']>$end_page) { $this->form->forms[$key]['page'] -= $n_tod; }
			else if($f['page']<$start_page) { unset($this->form->forms[$key]); }
		}


		// Update PageNumSubstitutions
		foreach($this->PageNumSubstitutions AS $k=>$v) {
			if($this->PageNumSubstitutions[$k]['from']>$end_page) { $this->PageNumSubstitutions[$k]['from'] -= $n_tod; }
			else if($this->PageNumSubstitutions[$k]['from']<$start_page) { unset($this->PageNumSubstitutions[$k]); }
		}

	unset($newarr);
	$this->page = count($this->pages);
}


//======================================================


function AcceptPageBreak() {
	if (count($this->cellBorderBuffer)) { $this->printcellbuffer(); }	// *TABLES*
	else if ($this->table_rotate) {
		if ($this->tablebuffer) { $this->printtablebuffer(); }
		return true;
	}
        	$this->ChangeColumn=0;
		return $this->autoPageBreak;
	return $this->autoPageBreak;
}


//----------- COLUMNS ---------------------


//==================================================================
function printcellbuffer() {
	if (count($this->cellBorderBuffer )) {
		sort($this->cellBorderBuffer);
		foreach($this->cellBorderBuffer AS $cbb) {
			$cba = unpack("A16dom/nbord/A1side/ns/dbw/a6ca/A10style/dx/dy/dw/dh/dmbl/dmbr/dmrt/dmrb/dmtl/dmtr/dmlt/dmlb/dcpd/dover/", $cbb);
			$side = $cba['side'];
			$details = array();
			$details[$side]['dom'] = (float) $cba['dom'];
			$details[$side]['s'] = $cba['s'];
			$details[$side]['w'] = $cba['bw'];
			$details[$side]['c'] = $cba['ca'];
			$details[$side]['style'] = trim($cba['style']);
			$details['mbw']['BL'] = $cba['mbl'];
			$details['mbw']['BR'] = $cba['mbr'];
			$details['mbw']['RT'] = $cba['mrt'];
			$details['mbw']['RB'] = $cba['mrb'];
			$details['mbw']['TL'] = $cba['mtl'];
			$details['mbw']['TR'] = $cba['mtr'];
			$details['mbw']['LT'] = $cba['mlt'];
			$details['mbw']['LB'] = $cba['mlb'];
			$details['cellposdom'] = $cba['cpd'];
			$details['p'] = $side;
			if ($cba['over']==1) { $details[$side]['overlay'] = true;  }
			else { $details[$side]['overlay'] = false; }
			$this->_tableRect($cba['x'],$cba['y'],$cba['w'],$cba['h'],$cba['bord'],$details, false, false);

		}
		$this->cellBorderBuffer = array();
	}
}
//==================================================================
function printtablebuffer() {

	if (!$this->table_rotate) {
		$this->pages[$this->page] .= $this->tablebuffer;
		foreach($this->tbrot_Links AS $p => $l) {
		   foreach($l AS $v) {
			$this->PageLinks[$p][] = $v;
		   }
		}
		$this->tbrot_Links = array();




		return;
	}
	// else if rotated
	$lm = $this->lMargin + $this->blk[$this->blklvl]['outer_left_margin'] + $this->blk[$this->blklvl]['border_left']['w'] + $this->blk[$this->blklvl]['padding_left'];
	$pw = $this->blk[$this->blklvl]['inner_width'];
	//Start Transformation
	$this->pages[$this->page] .= $this->StartTransform(true)."\n";

	if ($this->table_rotate > 1) {	// clockwise
	   if ($this->tbrot_align == 'L') {
		$xadj = $this->tbrot_h ;	// align L (as is)
	   }
	   else if ($this->tbrot_align == 'R') {
		$xadj = $lm-$this->tbrot_x0+($pw) ;	// align R
	   }
	   else {
		$xadj = $lm-$this->tbrot_x0+(($pw + $this->tbrot_h)/2) ;	// align C
	   }
	   $yadj = 0;
	}
	else {	// anti-clockwise
	   if ($this->tbrot_align == 'L') {
		$xadj = 0 ;	// align L (as is)
	   }
	   else if ($this->tbrot_align == 'R') {
		$xadj = $lm-$this->tbrot_x0+($pw - $this->tbrot_h) ;	// align R
	   }
	   else {
		$xadj = $lm-$this->tbrot_x0+(($pw - $this->tbrot_h)/2) ;	// align C
	   }
	   $yadj = $this->tbrot_w;
	}


	$this->pages[$this->page] .= $this->transformTranslate($xadj, $yadj , true)."\n";
	$this->pages[$this->page] .= $this->transformRotate($this->table_rotate, $this->tbrot_x0 , $this->tbrot_y0 , true)."\n";

	// Now output the adjusted values
	$this->pages[$this->page] .= $this->tablebuffer;


	foreach($this->tbrot_Links AS $p => $l) {
	    foreach($l AS $v) {
		$w = $v[2]/_MPDFK;
		$h = $v[3]/_MPDFK;
		$ax = ($v[0]/_MPDFK) - $this->tbrot_x0;
		$ay = (($this->hPt-$v[1])/_MPDFK) - $this->tbrot_y0;
		if ($this->table_rotate > 1) {	// clockwise
			$bx = $this->tbrot_x0+$xadj-$ay-$h;
			$by = $this->tbrot_y0+$yadj+$ax;
		}
		else {
			$bx = $this->tbrot_x0+$xadj+$ay;
			$by = $this->tbrot_y0+$yadj-$ax-$w;
		}
		$v[0] = $bx*_MPDFK;
		$v[1] = ($this->h-$by)*_MPDFK;
		$v[2] = $h*_MPDFK;	// swap width and height
		$v[3] = $w*_MPDFK;
		$this->PageLinks[$p][] = $v;
	    }
	}
	$this->tbrot_Links = array();
	foreach($this->internallink AS $key => $f) {
		if (is_array($f) && isset($f['tbrot'])) {
			$f['Y'] = $this->tbrot_y0;
			$f['PAGE'] = $this->page;
			unset($f['tbrot']);
			$this->internallink[$key] = $f;
		}
	}







	$this->tbrot_Reference = array();
	$this->tbrot_BMoutlines = array();
	$this->tbrot_toc = array();

	//Stop Transformation
	$this->pages[$this->page] .= $this->StopTransform(true)."\n";


	$this->y = $this->tbrot_y0 + $this->tbrot_w;
	$this->x = $this->lMargin;

	$this->tablebuffer = '';
}

//==================================================================
// Keep-with-table This buffers contents of h1-6 to keep on page with table
function printkwtbuffer() {
	if (!$this->kwt_moved) {
		foreach($this->kwt_buffer AS $s) { $this->pages[$this->page] .= $s['s']."\n"; }
		foreach($this->kwt_Links AS $p => $l) {
		   foreach($l AS $v) {
			$this->PageLinks[$p][] = $v;
		   }
		}
		$this->kwt_Links = array();




		return;
	}

	//Start Transformation
	$this->pages[$this->page] .= $this->StartTransform(true)."\n";
	$xadj = $this->lMargin - $this->kwt_x0 ;
	//$yadj = $this->y - $this->kwt_y0 ;
	$yadj = $this->tMargin - $this->kwt_y0 ;

	$this->pages[$this->page] .= $this->transformTranslate($xadj, $yadj , true)."\n";

	// Now output the adjusted values
	foreach($this->kwt_buffer AS $s) { $this->pages[$this->page] .= $s['s']."\n"; }

	// Adjust hyperLinks
	foreach($this->kwt_Links AS $p => $l) {
	    foreach($l AS $v) {
		$bx = $this->kwt_x0+$xadj;
		$by = $this->kwt_y0+$yadj;
		$v[0] = $bx*_MPDFK;
		$v[1] = ($this->h-$by)*_MPDFK;
		$this->PageLinks[$p][] = $v;
	    }
	}
	foreach($this->internallink AS $key => $f) {
		if (is_array($f) && isset($f['kwt'])) {
			$f['Y'] += $yadj;
			$f['PAGE'] = $this->page;
			unset($f['kwt']);
			$this->internallink[$key] = $f;
		}
	}





	$this->kwt_Links = array();
	$this->kwt_Annots = array();

	$this->kwt_Reference = array();
	$this->kwt_BMoutlines = array();
	$this->kwt_toc = array();
	//Stop Transformation
	$this->pages[$this->page] .= $this->StopTransform(true)."\n";

	$this->kwt_buffer = array();

	$this->y += $this->kwt_height;
}



//==================================================================

function printfloatbuffer() {
	if (count($this->floatbuffer)) {
		$this->objectbuffer = $this->floatbuffer;
		$this->printobjectbuffer(false);
		$this->objectbuffer = array();
		$this->floatbuffer = array();
		$this->floatmargins = array();
	}
}
//==================================================================

function printdivbuffer() {
	$p1 = $this->blk[$this->blklvl]['startpage'];
	$p2 = $this->page;
	$bottom[$p1] = $this->ktBlock[$p1]['bottom_margin'];
	$bottom[$p2] = $this->y;	// $this->ktBlock[$p2]['bottom_margin'];
	$top[$p1] = $this->kt_y00;

	$top2 = $this->h;
	foreach($this->divbuffer AS $key=>$s) {
		if ($s['page'] == $p2) {
			$top2 = MIN($s['y'], $top2);
		}
	}
	$top[$p2] = $top2;
	$height[$p1] = ($bottom[$p1] - $top[$p1]);
	$height[$p2] = ($bottom[$p2] - $top[$p2]);
	$xadj[$p1] = $this->MarginCorrection;
	$yadj[$p1] = -($top[$p1] - $top[$p2]);
	$xadj[$p2] = 0;
	$yadj[$p2] = $height[$p1];

	// Output without any transformation
	if ($this->ColActive || !$this->keep_block_together || $this->blk[$this->blklvl]['startpage'] == $this->page || ($this->page - $this->blk[$this->blklvl]['startpage']) > 1 || ($height[$p1]+$height[$p2]) > $this->h) {
		foreach($this->divbuffer AS $s) { $this->pages[$s['page']] .= $s['s']."\n"; }
		foreach($this->ktLinks AS $p => $l) {
		   foreach($l AS $v) {
			$this->PageLinks[$p][] = $v;
		   }
		}
		foreach($this->ktForms AS $key => $f) {
			$this->form->forms[$f['n']] = $f;
		}



		$this->divbuffer = array();
		$this->ktLinks = array();
		$this->ktAnnots = array();
		$this->ktForms = array();
		$this->ktBlock = array();
		$this->ktReference = array();
		$this->ktBMoutlines = array();
		$this->_kttoc = array();
		$this->keep_block_together = 0;
		return;
	}
	else {
	   $np = '';
	   $pre = '';
	   $ispre = true;
	   foreach($this->divbuffer AS $key=>$s) {
		// callback function
		$t = $s['s'];
		$p = $s['page'];
		if (preg_match('/(___PAGE___START'.date('jY').')/', $t)) { $ispre = false; }
		else if (preg_match('/(___HEADER___MARKER'.date('jY').')/', $t)) {
			$np .= $t."\n".$pre;
			continue;
		}
		else {
		  $t = preg_replace('/BT (\d+\.\d\d+) (\d+\.\d\d+) Td/e',"\$this->blockAdjust('Td',_MPDFK,$xadj[$p],$yadj[$p],'\\1','\\2')",$t);
		  $t = preg_replace('/(\d+\.\d\d+) (\d+\.\d\d+) (\d+\.\d\d+) ([\-]{0,1}\d+\.\d\d+) re/e',"\$this->blockAdjust('re',_MPDFK,$xadj[$p],$yadj[$p],'\\1','\\2','\\3','\\4')",$t);
		  $t = preg_replace('/(\d+\.\d\d+) (\d+\.\d\d+) l/e',"\$this->blockAdjust('l',_MPDFK,$xadj[$p],$yadj[$p],'\\1','\\2')",$t);
		  $t = preg_replace('/q (\d+\.\d\d+) 0 0 (\d+\.\d\d+) (\d+\.\d\d+) (\d+\.\d\d+) cm \/(I|FO)/e',"\$this->blockAdjust('img',_MPDFK,$xadj[$p],$yadj[$p],'\\1','\\2','\\3','\\4','\\5')",$t);
		  $t = preg_replace('/(\d+\.\d\d+) (\d+\.\d\d+) m/e',"\$this->blockAdjust('draw',_MPDFK,$xadj[$p],$yadj[$p],'\\1','\\2')",$t);
		  $t = preg_replace('/(\d+\.\d\d+) (\d+\.\d\d+) (\d+\.\d\d+) (\d+\.\d\d+) (\d+\.\d\d+) (\d+\.\d\d+) c/e',"\$this->blockAdjust('bezier',_MPDFK,$xadj[$p],$yadj[$p],'\\1','\\2','\\3','\\4','\\5','\\6')",$t);
		}
	      if ($ispre) $pre .= $t."\n";
		else $np .= $t."\n";
	   }
	   if ($ispre && $pre) $np = $pre ."\n". $np;
	   $this->pages[$this->page] .= $np;

	   // Adjust hyperLinks
	   foreach($this->ktLinks AS $p => $l) {
	    foreach($l AS $v) {
		$v[0] += ($xadj[$p]*_MPDFK);
		$v[1] -= ($yadj[$p]*_MPDFK);
		$this->PageLinks[$p2][] = $v;
	    }
	   }
	   foreach($this->ktForms AS $key => $f) {
		$p = $f['page'];
		$f['x'] += ($xadj[$p]);
		$f['y'] += ($yadj[$p]);
		$f['page'] = $p2;
		$this->form->forms[$f['n']] = $f;
	   }
	   foreach($this->internallink AS $key => $f) {
		if (is_array($f) && isset($f['kt'])) {
			$f['Y'] += ($yadj[$f['PAGE']]);
			$f['PAGE'] = $p2;
			unset($f['kt']);
			$this->internallink[$key] = $f;
		}
	   }




	   $this->y = $top[$p2] + $height[$p1] + $height[$p2];
	   $this->x = $this->lMargin;

	   $this->divbuffer = array();
	   $this->ktLinks = array();
	   $this->ktAnnots = array();
	   $this->ktForms = array();
	   $this->ktBlock = array();
	   $this->ktReference = array();
	   $this->ktBMoutlines = array();
	   $this->_kttoc = array();
	   $this->keep_block_together = 0;
	}
}


//==================================================================
// Added ELLIPSES and CIRCLES
function Circle($x,$y,$r,$style='S') {
	$this->Ellipse($x,$y,$r,$r,$style);
}

function Ellipse($x,$y,$rx,$ry,$style='S') {
	if($style=='F') { $op='f'; }
	elseif($style=='FD' or $style=='DF') { $op='B'; }
	else { $op='S'; }
	$lx=4/3*(M_SQRT2-1)*$rx;
	$ly=4/3*(M_SQRT2-1)*$ry;
	$h=$this->h;
	$this->_out(sprintf('%.3F %.3F m %.3F %.3F %.3F %.3F %.3F %.3F c', ($x+$rx)*_MPDFK,($h-$y)*_MPDFK, ($x+$rx)*_MPDFK,($h-($y-$ly))*_MPDFK, ($x+$lx)*_MPDFK,($h-($y-$ry))*_MPDFK, $x*_MPDFK,($h-($y-$ry))*_MPDFK));
	$this->_out(sprintf('%.3F %.3F %.3F %.3F %.3F %.3F c', ($x-$lx)*_MPDFK,($h-($y-$ry))*_MPDFK, 	($x-$rx)*_MPDFK,($h-($y-$ly))*_MPDFK, 	($x-$rx)*_MPDFK,($h-$y)*_MPDFK));
	$this->_out(sprintf('%.3F %.3F %.3F %.3F %.3F %.3F c', ($x-$rx)*_MPDFK,($h-($y+$ly))*_MPDFK, ($x-$lx)*_MPDFK,($h-($y+$ry))*_MPDFK, $x*_MPDFK,($h-($y+$ry))*_MPDFK));
	$this->_out(sprintf('%.3F %.3F %.3F %.3F %.3F %.3F c %s', ($x+$lx)*_MPDFK,($h-($y+$ry))*_MPDFK, ($x+$rx)*_MPDFK,($h-($y+$ly))*_MPDFK, ($x+$rx)*_MPDFK,($h-$y)*_MPDFK, $op));
}






// ====================================================
// ====================================================

//
// ****************************
// ****************************


function SetSubstitutions() {
	$subsarray = array();
	@include(_MPDF_PATH.'includes/subs_win-1252.php');
	$this->substitute = array();
	foreach($subsarray AS $key => $val) {
		$this->substitute[code2utf($key)] = $val;
	}
}


function SubstituteChars($html) {
	// only substitute characters between tags
	if (count($this->substitute)) {
		$a=preg_split('/(<.*?>)/ms',$html,-1,PREG_SPLIT_DELIM_CAPTURE);
		$html = '';
		foreach($a as $i => $e) {
			if($i%2==0) {
			   $e = strtr($e, $this->substitute);
			}
			$html .= $e;
		}
	}
	return $html;
}


function SubstituteCharsSIP(&$writehtml_a, &$writehtml_i, &$writehtml_e) {
	if (preg_match("/^(.*?)([\x{20000}-\x{2FFFF}]+)(.*)/u", $writehtml_e, $m)) {
	   if (isset($this->CurrentFont['sipext']) && $this->CurrentFont['sipext']) {
		$font = $this->CurrentFont['sipext'];
		if (!in_array($font, $this->available_unifonts)) { return 0; }
		$writehtml_a[$writehtml_i] = $writehtml_e = $m[1];
		array_splice($writehtml_a, $writehtml_i+1, 0, array('span style="font-family: '.$font.'"', $m[2], '/span', $m[3]));
		$this->subPos = $writehtml_i;
		return 4;
	   }
	}
	return 0;
}

// If core font is selected in document which is not onlyCoreFonts - substitute with non-core font
function SubstituteCharsNonCore(&$writehtml_a, &$writehtml_i, &$writehtml_e) {
	if (mb_convert_encoding(mb_convert_encoding($writehtml_e, $this->mb_enc, "UTF-8"), "UTF-8", $this->mb_enc) == $writehtml_e) {
		return 0;
	}
	$cw = &$this->CurrentFont['cw'];
	$unicode = $this->UTF8StringToArray($writehtml_e, false);
	$start = -1;
	$end = 0;
	$flag = 0;
	$ftype = '';
	$u = array();
	if (!$this->subArrMB) {
		include(_MPDF_PATH.'includes/subs_core.php');
		$this->subArrMB['a'] = $aarr;
		$this->subArrMB['s'] = $sarr;
		$this->subArrMB['z'] = $zarr;
	}
	foreach($unicode AS $c => $char) {
		if (($char> 127 || ($flag==1 && $char==32)) && $char != 173 && (!isset($this->subArrMB['a'][$char]) || ($flag==1 && $char==32)) && ($char<1536 ||  ($char>1791 && $char < 2304) || $char>3455)) {
			if ($flag==0) { $start=$c; }
			$flag=1;
			$u[] = $char;
		}
		else if ($flag>0) { $end=$c-1; break; }
	}
	if ($flag>0 && !$end) { $end=count($unicode)-1; }
	if ($start==-1) { return 0; }
	// TRY IN BACKUP SUBS FONT
	if (!is_array($this->backupSubsFont)) { $this->backupSubsFont = array("$this->backupSubsFont"); }
	foreach($this->backupSubsFont AS $bsfctr=>$bsf) {
		if ($this->fonttrans[$bsf] == 'chelvetica' || $this->fonttrans[$bsf] == 'ctimes' || $this->fonttrans[$bsf] == 'ccourier') { continue; }
		$font = $bsf;
		unset($cw);
		$cw = '';
		if (isset($this->fonts[$font])) { $cw = &$this->fonts[$font]['cw']; }
		else if (file_exists(_MPDF_TTFONTDATAPATH.$font.'.cw.dat')) { $cw = @file_get_contents(_MPDF_TTFONTDATAPATH.$font.'.cw.dat'); }
		else {

			$prevFontFamily = $this->FontFamily;
			$prevFontStyle = $this->currentfontstyle;
			$prevFontSizePt = $this->FontSizePt;
			$this->SetFont($bsf, '', '', false);
			$cw = @file_get_contents(_MPDF_TTFONTDATAPATH.$font.'.cw.dat');
			$this->SetFont($prevFontFamily, $prevFontStyle, $prevFontSizePt, false);
		}
		if (!$cw) { continue; }
		$l = 0;
		foreach($u AS $char) {
			if ($char == 173 || $this->_charDefined($cw,$char) || ($char>1536 && $char<1791) || ($char>2304 && $char<3455 )) {
				$l++;
			}
			else {
				if ($l==0 && $bsfctr == (count($this->backupSubsFont)-1)) {	// Not found even in last backup font
					$cont = mb_substr($writehtml_e, $start+1);
					$writehtml_e = mb_substr($writehtml_e, 0, $start+1, 'UTF-8');
					array_splice($writehtml_a, $writehtml_i+1, 0, array('', $cont));
					$this->subPos = $writehtml_i+1;
					return 2;
				}
				else { break; }
			}
		}
		if ($l > 0) {
			$patt = mb_substr($writehtml_e, $start, $l, 'UTF-8');
			if (preg_match("/(.*?)(".preg_quote($patt,'/').")(.*)/u", $writehtml_e, $m)) {
				$writehtml_e = $m[1];
				array_splice($writehtml_a, $writehtml_i+1, 0, array('span style="font-family: '.$font.'"', $m[2], '/span', $m[3]));
				$this->subPos = $writehtml_i+3;
				return 4;
			}
		}
	}

	unset($cw);
	return 0;
}


function SubstituteCharsMB(&$writehtml_a, &$writehtml_i, &$writehtml_e) {
	$cw = &$this->CurrentFont['cw'];
	$unicode = $this->UTF8StringToArray($writehtml_e, false);
	$start = -1;
	$end = 0;
	$flag = 0;
	$ftype = '';
	$u = array();
	foreach($unicode AS $c => $char) {
		if (($flag == 0 || $flag==2) && (!$this->_charDefined($cw,$char) || ($flag==2 && $char==32)) && $this->checkSIP && $char > 131071) { 	// Unicode Plane 2 (SIP)
			if (in_array($this->FontFamily ,$this->available_CJK_fonts)) { return 0; }
			if ($flag==0) { $start=$c; }
			$flag=2;
			$u[] = $char;
		}
		//else if (($flag == 0 || $flag==1) && $char != 173 && !$this->_charDefined($cw,$char) && ($char<1423 ||  ($char>3583 && $char < 11263))) {
		else if (($flag == 0 || $flag==1) && $char != 173 && (!$this->_charDefined($cw,$char) || ($flag==1 && $char==32)) && ($char<1536 ||  ($char>1791 && $char < 2304) || $char>3455)) {
			if ($flag==0) { $start=$c; }
			$flag=1;
			$u[] = $char;
		}
		else if ($flag>0) { $end=$c-1; break; }
	}
	if ($flag>0 && !$end) { $end=count($unicode)-1; }
	if ($start==-1) { return 0; }
	if ($flag == 2) { 	// SIP
		// Check if current CJK font has a ext-B related font
	   if (isset($this->CurrentFont['sipext']) && $this->CurrentFont['sipext']) {
		$font = $this->CurrentFont['sipext'];
		unset($cw);
		$cw = '';
		if (isset($this->fonts[$font])) { $cw = &$this->fonts[$font]['cw']; }
		else if (file_exists(_MPDF_TTFONTDATAPATH.$font.'.cw.dat')) { $cw = @file_get_contents(_MPDF_TTFONTDATAPATH.$font.'.cw.dat'); }
		else {
			$prevFontFamily = $this->FontFamily;
			$prevFontStyle = $this->currentfontstyle;
			$prevFontSizePt = $this->FontSizePt;
			$this->SetFont($font, '', '', false);
			$cw = @file_get_contents(_MPDF_TTFONTDATAPATH.$font.'.cw.dat');
			$this->SetFont($prevFontFamily, $prevFontStyle, $prevFontSizePt, false);
		}
		if (!$cw) { return 0; }
		$l = 0;
		foreach($u AS $char) {
			if ($this->_charDefined($cw,$char) || $char > 131071) {
				$l++;
			}
			else { break; }
		}
		if ($l > 0) {
			$patt = mb_substr($writehtml_e, $start, $l);
			if (preg_match("/(.*?)(".preg_quote($patt,'/').")(.*)/u", $writehtml_e, $m)) {
				$writehtml_e = $m[1];
				array_splice($writehtml_a, $writehtml_i+1, 0, array('span style="font-family: '.$font.'"', $m[2], '/span', $m[3]));
				$this->subPos = $writehtml_i+3;
				return 4;
			}
		}
	   }
		// Check Backup SIP font (defined in config_fonts.php)
	   if (isset($this->backupSIPFont) && $this->backupSIPFont) {
		if ($this->currentfontfamily != $this->backupSIPFont) { $font = $this->backupSIPFont; }
		else { unset($cw); return 0; }
		unset($cw);
		$cw = '';
		if (isset($this->fonts[$font])) { $cw = &$this->fonts[$font]['cw']; }
		else if (file_exists(_MPDF_TTFONTDATAPATH.$font.'.cw.dat')) { $cw = @file_get_contents(_MPDF_TTFONTDATAPATH.$font.'.cw.dat'); }
		else {
			$prevFontFamily = $this->FontFamily;
			$prevFontStyle = $this->currentfontstyle;
			$prevFontSizePt = $this->FontSizePt;
			$this->SetFont($this->backupSIPFont, '', '', false);
			$cw = @file_get_contents(_MPDF_TTFONTDATAPATH.$font.'.cw.dat');
			$this->SetFont($prevFontFamily, $prevFontStyle, $prevFontSizePt, false);
		}
		if (!$cw) { return 0; }
		$l = 0;
		foreach($u AS $char) {
			if ($this->_charDefined($cw,$char) || $char > 131071) {
				$l++;
			}
			else { break; }
		}
		if ($l > 0) {
			$patt = mb_substr($writehtml_e, $start, $l);
			if (preg_match("/(.*?)(".preg_quote($patt,'/').")(.*)/u", $writehtml_e, $m)) {
				$writehtml_e = $m[1];
				array_splice($writehtml_a, $writehtml_i+1, 0, array('span style="font-family: '.$font.'"', $m[2], '/span', $m[3]));
				$this->subPos = $writehtml_i+3;
				return 4;
			}
		}
	   }
	   return 0;
	}


	// FIRST TRY CORE FONTS
	if (!$this->PDFA && !$this->PDFX) {
	  $repl = array();
	  if (!$this->subArrMB) {
		include(_MPDF_PATH.'includes/subs_core.php');
		$this->subArrMB['a'] = $aarr;
		$this->subArrMB['s'] = $sarr;
		$this->subArrMB['z'] = $zarr;
	  }
	  if (isset($this->subArrMB['a'][$u[0]])) {
		$font = 'tta'; $ftype = 'C';
		foreach($u AS $char) {
			if ($this->subArrMB['a'][$char]) { $repl[] = $this->subArrMB['a'][$char]; }
			else { break; }
		}
	  }
	  else if (isset($this->subArrMB['z'][$u[0]])) {
		$font = 'ttz'; $ftype = 'C';
		foreach($u AS $char) {
			if ($this->subArrMB['z'][$char]) { $repl[] = $this->subArrMB['z'][$char]; }
			else { break; }
		}
	  }
	  else if (isset($this->subArrMB['s'][$u[0]])) {
		$font = 'tts'; $ftype = 'C';
		foreach($u AS $char) {
			if ($this->subArrMB['s'][$char]) { $repl[] = $this->subArrMB['s'][$char]; }
			else { break; }
		}
	  }
	  if ($ftype=='C') {
		$patt = mb_substr($writehtml_e, $start, count($repl));
		if (preg_match("/(.*?)(".preg_quote($patt,'/').")(.*)/u", $writehtml_e, $m)) {
			$writehtml_e = $m[1];
			array_splice($writehtml_a, $writehtml_i+1, 0, array($font, implode('|', $repl), '/'.$font, $m[3]));	// e.g. <tts>
			$this->subPos = $writehtml_i+3;
			return 4;
		}
		return 0;
	  }
	}

	// FIND IN DEFAULT FONT - removed mPDF 5.0

	// LASTLY TRY IN BACKUP SUBS FONT
	if (!is_array($this->backupSubsFont)) { $this->backupSubsFont = array("$this->backupSubsFont"); }
	foreach($this->backupSubsFont AS $bsfctr=>$bsf) {
		if ($this->currentfontfamily != $bsf) { $font = $bsf; }
		else { continue; }
		unset($cw);
		$cw = '';
		if (isset($this->fonts[$font])) { $cw = &$this->fonts[$font]['cw']; }
		else if (file_exists(_MPDF_TTFONTDATAPATH.$font.'.cw.dat')) { $cw = @file_get_contents(_MPDF_TTFONTDATAPATH.$font.'.cw.dat'); }
		else {
			$prevFontFamily = $this->FontFamily;
			$prevFontStyle = $this->currentfontstyle;
			$prevFontSizePt = $this->FontSizePt;
			$this->SetFont($bsf, '', '', false);
			$cw = @file_get_contents(_MPDF_TTFONTDATAPATH.$font.'.cw.dat');
			$this->SetFont($prevFontFamily, $prevFontStyle, $prevFontSizePt, false);
		}
		if (!$cw) { continue; }
		$l = 0;
		foreach($u AS $char) {
			if ($char == 173 || $this->_charDefined($cw,$char) || ($char>1536 && $char<1791) || ($char>2304 && $char<3455 )) { 	// Arabic and Indic
				$l++;
			}
			else {
				if ($l==0 && $bsfctr == (count($this->backupSubsFont)-1)) {	// Not found even in last backup font
					$cont = mb_substr($writehtml_e, $start+1);
					$writehtml_e = mb_substr($writehtml_e, 0, $start+1);
					array_splice($writehtml_a, $writehtml_i+1, 0, array('', $cont));
					$this->subPos = $writehtml_i+1;
					return 2;
				}
				else { break; }
			}
		}
		if ($l > 0) {
			$patt = mb_substr($writehtml_e, $start, $l);
			if (preg_match("/(.*?)(".preg_quote($patt,'/').")(.*)/u", $writehtml_e, $m)) {
				$writehtml_e = $m[1];
				array_splice($writehtml_a, $writehtml_i+1, 0, array('span style="font-family: '.$font.'"', $m[2], '/span', $m[3]));
				$this->subPos = $writehtml_i+3;
				return 4;
			}
		}
	}

	unset($cw);
	return 0;
}


function setHiEntitySubstitutions() {
	$entarr = array (
  'nbsp' => '160',  'iexcl' => '161',  'cent' => '162',  'pound' => '163',  'curren' => '164',  'yen' => '165',  'brvbar' => '166',  'sect' => '167',
  'uml' => '168',  'copy' => '169',  'ordf' => '170',  'laquo' => '171',  'not' => '172',  'shy' => '173',  'reg' => '174',  'macr' => '175',
  'deg' => '176',  'plusmn' => '177',  'sup2' => '178',  'sup3' => '179',  'acute' => '180',  'micro' => '181',  'para' => '182',  'middot' => '183',
  'cedil' => '184',  'sup1' => '185',  'ordm' => '186',  'raquo' => '187',  'frac14' => '188',  'frac12' => '189',  'frac34' => '190',
  'iquest' => '191',  'Agrave' => '192',  'Aacute' => '193',  'Acirc' => '194',  'Atilde' => '195',  'Auml' => '196',  'Aring' => '197',
  'AElig' => '198',  'Ccedil' => '199',  'Egrave' => '200',  'Eacute' => '201',  'Ecirc' => '202',  'Euml' => '203',  'Igrave' => '204',
  'Iacute' => '205',  'Icirc' => '206',  'Iuml' => '207',  'ETH' => '208',  'Ntilde' => '209',  'Ograve' => '210',  'Oacute' => '211',
  'Ocirc' => '212',  'Otilde' => '213',  'Ouml' => '214',  'times' => '215',  'Oslash' => '216',  'Ugrave' => '217',  'Uacute' => '218',
  'Ucirc' => '219',  'Uuml' => '220',  'Yacute' => '221',  'THORN' => '222',  'szlig' => '223',  'agrave' => '224',  'aacute' => '225',
  'acirc' => '226',  'atilde' => '227',  'auml' => '228',  'aring' => '229',  'aelig' => '230',  'ccedil' => '231',  'egrave' => '232',
  'eacute' => '233',  'ecirc' => '234',  'euml' => '235',  'igrave' => '236',  'iacute' => '237',  'icirc' => '238',  'iuml' => '239',
  'eth' => '240',  'ntilde' => '241',  'ograve' => '242',  'oacute' => '243',  'ocirc' => '244',  'otilde' => '245',  'ouml' => '246',
  'divide' => '247',  'oslash' => '248',  'ugrave' => '249',  'uacute' => '250',  'ucirc' => '251',  'uuml' => '252',  'yacute' => '253',
  'thorn' => '254',  'yuml' => '255',  'OElig' => '338',  'oelig' => '339',  'Scaron' => '352',  'scaron' => '353',  'Yuml' => '376',
  'fnof' => '402',  'circ' => '710',  'tilde' => '732',  'Alpha' => '913',  'Beta' => '914',  'Gamma' => '915',  'Delta' => '916',
  'Epsilon' => '917',  'Zeta' => '918',  'Eta' => '919',  'Theta' => '920',  'Iota' => '921',  'Kappa' => '922',  'Lambda' => '923',
  'Mu' => '924',  'Nu' => '925',  'Xi' => '926',  'Omicron' => '927',  'Pi' => '928',  'Rho' => '929',  'Sigma' => '931',  'Tau' => '932',
  'Upsilon' => '933',  'Phi' => '934',  'Chi' => '935',  'Psi' => '936',  'Omega' => '937',  'alpha' => '945',  'beta' => '946',  'gamma' => '947',
  'delta' => '948',  'epsilon' => '949',  'zeta' => '950',  'eta' => '951',  'theta' => '952',  'iota' => '953',  'kappa' => '954',
  'lambda' => '955',  'mu' => '956',  'nu' => '957',  'xi' => '958',  'omicron' => '959',  'pi' => '960',  'rho' => '961',  'sigmaf' => '962',
  'sigma' => '963',  'tau' => '964',  'upsilon' => '965',  'phi' => '966',  'chi' => '967',  'psi' => '968',  'omega' => '969',
  'thetasym' => '977',  'upsih' => '978',  'piv' => '982',  'ensp' => '8194',  'emsp' => '8195',  'thinsp' => '8201',  'zwnj' => '8204',
  'zwj' => '8205',  'lrm' => '8206',  'rlm' => '8207',  'ndash' => '8211',  'mdash' => '8212',  'lsquo' => '8216',  'rsquo' => '8217',
  'sbquo' => '8218',  'ldquo' => '8220',  'rdquo' => '8221',  'bdquo' => '8222',  'dagger' => '8224',  'Dagger' => '8225',  'bull' => '8226',
  'hellip' => '8230',  'permil' => '8240',  'prime' => '8242',  'Prime' => '8243',  'lsaquo' => '8249',  'rsaquo' => '8250',  'oline' => '8254',
  'frasl' => '8260',  'euro' => '8364',  'image' => '8465',  'weierp' => '8472',  'real' => '8476',  'trade' => '8482',  'alefsym' => '8501',
  'larr' => '8592',  'uarr' => '8593',  'rarr' => '8594',  'darr' => '8595',  'harr' => '8596',  'crarr' => '8629',  'lArr' => '8656',
  'uArr' => '8657',  'rArr' => '8658',  'dArr' => '8659',  'hArr' => '8660',  'forall' => '8704',  'part' => '8706',  'exist' => '8707',
  'empty' => '8709',  'nabla' => '8711',  'isin' => '8712',  'notin' => '8713',  'ni' => '8715',  'prod' => '8719',  'sum' => '8721',
  'minus' => '8722',  'lowast' => '8727',  'radic' => '8730',  'prop' => '8733',  'infin' => '8734',  'ang' => '8736',  'and' => '8743',
  'or' => '8744',  'cap' => '8745',  'cup' => '8746',  'int' => '8747',  'there4' => '8756',  'sim' => '8764',  'cong' => '8773',
  'asymp' => '8776',  'ne' => '8800',  'equiv' => '8801',  'le' => '8804',  'ge' => '8805',  'sub' => '8834',  'sup' => '8835',  'nsub' => '8836',
  'sube' => '8838',  'supe' => '8839',  'oplus' => '8853',  'otimes' => '8855',  'perp' => '8869',  'sdot' => '8901',  'lceil' => '8968',
  'rceil' => '8969',  'lfloor' => '8970',  'rfloor' => '8971',  'lang' => '9001',  'rang' => '9002',  'loz' => '9674',  'spades' => '9824',
  'clubs' => '9827',  'hearts' => '9829',  'diams' => '9830',
 );
	foreach($entarr AS $key => $val) {
		$this->entsearch[] = '&'.$key.';';
		$this->entsubstitute[] = code2utf($val);
	}
}

function SubstituteHiEntities($html) {
	// converts html_entities > ASCII 127 to unicode
	// Leaves in particular &lt; to distinguish from tag marker
	if (count($this->entsearch)) {
		$html = str_replace($this->entsearch,$this->entsubstitute,$html);
	}
	return $html;
}


// Edited v1.2 Pass by reference; option to continue if invalid UTF-8 chars
function is_utf8(&$string) {
	if ($string === mb_convert_encoding(mb_convert_encoding($string, "UTF-32", "UTF-8"), "UTF-8", "UTF-32")) {
		return true;
	}
	else {
	  if ($this->ignore_invalid_utf8) {
		$string = mb_convert_encoding(mb_convert_encoding($string, "UTF-32", "UTF-8"), "UTF-8", "UTF-32") ;
		return true;
	  }
	  else {
		return false;
	  }
	}
}


function purify_utf8($html,$lo=true) {
	// For HTML
	// Checks string is valid UTF-8 encoded
	// converts html_entities > ASCII 127 to UTF-8
	// Only exception - leaves low ASCII entities e.g. &lt; &amp; etc.
	// Leaves in particular &lt; to distinguish from tag marker
	if (!$this->is_utf8($html)) {
		echo "<p><b>HTML contains invalid UTF-8 character(s)</b></p>";
		while (mb_convert_encoding(mb_convert_encoding($html, "UTF-32", "UTF-8"), "UTF-8", "UTF-32") != $html) {
			$a = iconv('UTF-8', 'UTF-8', $html);
			echo ($a);
			$pos = $start = strlen($a);
			$err = '';
			while ( ord(substr($html,$pos,1)) > 128 ) {
				$err .= '[[#'.ord(substr($html,$pos,1)).']]';
				$pos++;
			}
			echo '<span style="color:red; font-weight:bold">'.$err.'</span>';
			$html = substr($html, $pos);
		}
		echo $html;
		$this->Error("");
	}
	$html = preg_replace("/\r/", "", $html );

	// converts html_entities > ASCII 127 to UTF-8
	// Leaves in particular &lt; to distinguish from tag marker
	$html = $this->SubstituteHiEntities($html);

	// converts all &#nnn; or &#xHHH; to UTF-8 multibyte
	// If $lo==true then includes ASCII < 128
	$html = strcode2utf($html,$lo);
	return ($html);
}

function purify_utf8_text($txt) {
	// For TEXT
	// Make sure UTF-8 string of characters
	if(!mb_strlen($txt))
	{
		$txt = ' ';
	}
	
	if (!$this->is_utf8($txt)) { $this->Error("Text contains invalid UTF-8 character(s)"); }

	$txt = preg_replace("/\r/", "", $txt );

	return ($txt);
}
function all_entities_to_utf8($txt) {
	// converts txt_entities > ASCII 127 to UTF-8
	// Leaves in particular &lt; to distinguish from tag marker
	$txt = $this->SubstituteHiEntities($txt);

	// converts all &#nnn; or &#xHHH; to UTF-8 multibyte
	$txt = strcode2utf($txt);

	$txt = $this->lesser_entity_decode($txt);
	return ($txt);
}


// ====================================================

// ====================================================
// ====================================================

function StartTransform($returnstring=false) {
	  if ($returnstring) { return('q'); }
	  else { $this->_out('q'); }
}
function StopTransform($returnstring=false) {
	  if ($returnstring) { return('Q'); }
	  else { $this->_out('Q'); }
}
function transformScale($s_x, $s_y, $x='', $y='', $returnstring=false) {
	if ($x === '') {
		$x=$this->x;
	}
	if ($y === '') {
		$y=$this->y;
	}
	if (($s_x == 0) OR ($s_y == 0)) {
		$this->Error('Please do not use values equal to zero for scaling');
	}
	$y = ($this->h - $y) * _MPDFK;
	$x *= _MPDFK;
	//calculate elements of transformation matrix
	$s_x /= 100;
	$s_y /= 100;
	$tm[0] = $s_x;
	$tm[1] = 0;
	$tm[2] = 0;
	$tm[3] = $s_y;
	$tm[4] = $x * (1 - $s_x);
	$tm[5] = $y * (1 - $s_y);
	//scale the coordinate system
	if ($returnstring) { return($this->_transform($tm, true)); }
	else { $this->_transform($tm); }
}
function transformTranslate($t_x, $t_y, $returnstring=false) {
	//calculate elements of transformation matrix
	$tm[0] = 1;
	$tm[1] = 0;
	$tm[2] = 0;
	$tm[3] = 1;
	$tm[4] = $t_x * _MPDFK;
	$tm[5] = -$t_y * _MPDFK;
	//translate the coordinate system
	if ($returnstring) { return($this->_transform($tm, true)); }
	else { $this->_transform($tm); }
}
function transformRotate($angle, $x='', $y='', $returnstring=false) {
	if ($x === '') {
		$x=$this->x;
	}
	if ($y === '') {
		$y=$this->y;
	}
	$angle = -$angle;
	$y = ($this->h - $y) * _MPDFK;
	$x *= _MPDFK;
	//calculate elements of transformation matrix
	$tm[0] = cos(deg2rad($angle));
	$tm[1] = sin(deg2rad($angle));
	$tm[2] = -$tm[1];
	$tm[3] = $tm[0];
	$tm[4] = $x + $tm[1] * $y - $tm[0] * $x;
	$tm[5] = $y - $tm[0] * $y - $tm[1] * $x;
	//rotate the coordinate system around ($x,$y)
	if ($returnstring) { return($this->_transform($tm, true)); }
	else { $this->_transform($tm); }
}
function _transform($tm, $returnstring=false) {
	if ($returnstring) { return(sprintf('%.4F %.4F %.4F %.4F %.4F %.4F cm', $tm[0], $tm[1], $tm[2], $tm[3], $tm[4], $tm[5])); }
	else { $this->_out(sprintf('%.4F %.4F %.4F %.4F %.4F %.4F cm', $tm[0], $tm[1], $tm[2], $tm[3], $tm[4], $tm[5])); }
}




// AUTOFONT =========================
function AutoFont($html) {
	if ($this->onlyCoreFonts) { return $html; }
	$this->useLang = true;
	if ($this->autoFontGroupSize == 1) { $extra = $this->pregASCIIchars1; }
	else if ($this->autoFontGroupSize == 3) { $extra = $this->pregASCIIchars3; }
	else {  $extra = $this->pregASCIIchars2; }
	$n = '';
	$a=preg_split('/<(.*?)>/ms',$html,-1,PREG_SPLIT_DELIM_CAPTURE);
	foreach($a as $i => $e) {
	   if($i%2==0) {
		$e = strcode2utf($e);
		$e = $this->lesser_entity_decode($e);

		// Use U=FFF0 and U+FFF1 to mark start and end of span tags to prevent nesting occurring
		// "\xef\xbf\xb0" ##lthtmltag## "\xef\xbf\xb1" ##gthtmltag##





		if ($this->autoFontGroups & AUTOFONT_THAIVIET) {
			// THAI
			$e = preg_replace("/([\x{0E00}-\x{0E7F}".$extra."]*[\x{0E00}-\x{0E7F}][\x{0E00}-\x{0E7F}".$extra."]*)/u", "\xef\xbf\xb0span lang=\"th\"\xef\xbf\xb1\\1\xef\xbf\xb0/span\xef\xbf\xb1", $e);
			// Vietnamese
			$e = preg_replace("/([".$this->pregVIETchars .$this->pregVIETPluschars ."]*[".$this->pregVIETchars ."][".$this->pregVIETchars .$this->pregVIETPluschars ."]*)/u", "\xef\xbf\xb0span lang=\"vi\"\xef\xbf\xb1\\1\xef\xbf\xb0/span\xef\xbf\xb1", $e);
		}

		$e = preg_replace('/[&]/','&amp;',$e);
		$e = preg_replace('/[<]/','&lt;',$e);
		$e = preg_replace('/[>]/','&gt;',$e);
		$e = preg_replace("/(\xef\xbf\xb0span lang=\"([a-z\-A-Z]{2,5})\"\xef\xbf\xb1)\s+/",' \\1',$e);
		$e = preg_replace("/[ ]+(\xef\xbf\xb0\/span\xef\xbf\xb1)/",'\\1 ',$e);

		$e = preg_replace("/\xef\xbf\xb0span lang=\"([a-z\-A-Z]{2,5})\"\xef\xbf\xb1/","\xef\xbf\xb0span lang=\"\\1\" class=\"lang_\\1\"\xef\xbf\xb1",$e);

		$e = preg_replace("/\xef\xbf\xb0/",'<',$e);
		$e = preg_replace("/\xef\xbf\xb1/",'>',$e);

		$a[$i] = $e;
	   }
	   else {
		$a[$i] = '<'.$e.'>';
	   }
	}
	$n = implode('',$a);
	return $n;
}






//===========================
// Functions originally in htmltoolkit - moved mPDF 4.0

// Call-back function Used for usort in fn _tableWrite

function _cmpdom($a, $b) {
    return ($a["dom"] < $b["dom"]) ? -1 : 1;
}

function mb_strrev($str, $enc = 'utf-8'){
	$ch = array();
	$ch = preg_split('//u',$str);
	$revch = array_reverse($ch);
	return implode('',$revch);
}


// Callback function from function printdivbuffer in mpdf - keeping block together on one page
function blockAdjust($type,$k,$xadj,$yadj,$a,$b,$c=0,$d=0,$e=0,$f=0) {
   if ($type == 'Td') { 	// xpos,ypos
	$a += ($xadj * $k);
	$b -= ($yadj * $k);
	return 'BT '.sprintf('%.3F %.3F',$a,$b).' Td';
   }
   else if ($type == 're') { 	// xpos,ypos,width,height
	$a += ($xadj * $k);
	$b -= ($yadj * $k);
	return sprintf('%.3F %.3F %.3F %.3F',$a,$b,$c,$d).' re';
   }
   else if ($type == 'l') { 	// xpos,ypos,x2pos,y2pos
	$a += ($xadj * $k);
	$b -= ($yadj * $k);
	return sprintf('%.3F %.3F l',$a,$b);
   }
   else if ($type == 'img') { 	// width,height,xpos,ypos
	$c += ($xadj * $k);
	$d -= ($yadj * $k);
	return sprintf('q %.3F 0 0 %.3F %.3F %.3F',$a,$b,$c,$d).' cm /'.$e;
   }
   else if ($type == 'draw') { 	// xpos,ypos
	$a += ($xadj * $k);
	$b -= ($yadj * $k);
	return sprintf('%.3F %.3F m',$a,$b);
   }
   else if ($type == 'bezier') { 	// xpos,ypos,x2pos,y2pos,x3pos,y3pos
	$a += ($xadj * $k);
	$b -= ($yadj * $k);
	$c += ($xadj * $k);
	$d -= ($yadj * $k);
	$e += ($xadj * $k);
	$f -= ($yadj * $k);
	return sprintf('%.3F %.3F %.3F %.3F %.3F %.3F',$a,$b,$c,$d,$e,$f).' c';
   }
}



function ConvertColor($color="#000000"){
	$color = trim(strtolower($color));
	$c = false;
	if ($color=='transparent') { return false; }
	else if ($color=='inherit') { return false; }
	else if (isset($this->SVGcolors[$color])) $color = $this->SVGcolors[$color];

	if (preg_match('/^[\d]+$/',$color)) { $c = (array(1,$color)); }	// i.e. integer only
	else if ($color[0] == '#') { //case of #nnnnnn or #nnn
		$cor = preg_replace('/\s+.*/','',$color);	// in case of Background: #CCC url() x-repeat etc.
  		if (strlen($cor) == 4) { // Turn #RGB into #RRGGBB
		 	  $cor = "#" . $cor[1] . $cor[1] . $cor[2] . $cor[2] . $cor[3] . $cor[3];
		}
		$r = hexdec(substr($cor, 1, 2));
		$g = hexdec(substr($cor, 3, 2));
		$b = hexdec(substr($cor, 5, 2));
		$c = array(3,$r,$g,$b);
	}
	else if (preg_match('/(rgba|rgb|cmyka|cmyk|hsla|hsl|spot)\((.*?)\)/',$color,$m)) {
		$type= $m[1];
		$cores = explode(",", $m[2]);
		$ncores = count($cores);
		if (stristr($cores[0],'%') ) {
			$cores[0] += 0;
			if ($type=='rgb' || $type=='rgba') { $cores[0] = intval($cores[0]*255/100); }
		}
		if ($ncores>1 && stristr($cores[1],'%') ) {
			$cores[1] += 0;
			if ($type=='rgb' || $type=='rgba') { $cores[1] = intval($cores[1]*255/100); }
			if ($type=='hsl' || $type=='hsla') { $cores[1] = $cores[1]/100; }
		}
		if ($ncores>2 && stristr($cores[2],'%') ) {
			$cores[2] += 0;
			if ($type=='rgb' || $type=='rgba') { $cores[2] = intval($cores[2]*255/100); }
			if ($type=='hsl' || $type=='hsla') { $cores[2] = $cores[2]/100; }
		}
		if ($ncores>3 && stristr($cores[3],'%') ) {
			$cores[3] += 0;
		}

		if ($type=='rgb') { $c = array(3,$cores[0],$cores[1],$cores[2]); }
		else if ($type=='rgba') { $c = array(5,$cores[0],$cores[1],$cores[2],$cores[3]*100); }
		else if ($type=='cmyk') { $c = array(4,$cores[0],$cores[1],$cores[2],$cores[3]); }
		else if ($type=='cmyka') { $c = array(6,$cores[0],$cores[1],$cores[2],$cores[3],$cores[4]*100); }
		else if ($type=='hsl' || $type=='hsla') {
			$conv = $this->hsl2rgb($cores[0]/360,$cores[1],$cores[2]);
			if ($type=='hsl') { $c = array(3,$conv[0],$conv[1],$conv[2]); }
			else if ($type=='hsla') { $c = array(5,$conv[0],$conv[1],$conv[2],$cores[3]*100); }
		}
		else if ($type=='spot') {
			$name = strtoupper(trim($cores[0]));
			if(!isset($this->spotColors[$name])) $this->Error('Undefined spot color: '.$name);
			$c = array(2,$this->spotColors[$name]['i'],$cores[1]);
		}
	}


	// $this->restrictColorSpace
	// 1 - allow GRAYSCALE only [convert CMYK/RGB->gray]
	// 2 - allow RGB / SPOT COLOR / Grayscale [convert CMYK->RGB]
	// 3 - allow CMYK / SPOT COLOR / Grayscale [convert RGB->CMYK]
	if ($this->PDFA || $this->PDFX || $this->restrictColorSpace) {
		if ($c[0]==1) {	// GRAYSCALE
		}
		else if ($c[0]==2) {	// SPOT COLOR
			if (!isset($this->spotColorIDs[$c[1]])) { die('Error: Spot colour has not been defined - '.$this->spotColorIDs[$c[1]]); }
			if ($this->PDFA) {
				if ($this->PDFA && !$this->PDFAauto) { $this->PDFAXwarnings[] = "Spot color specified '".$this->spotColorIDs[$c[1]]."' (converted to process color)"; }
				if ($this->restrictColorSpace!=3) {
					$sp = $this->spotColors[$this->spotColorIDs[$c[1]]];
					$c = $this->cmyk2rgb(array(4,$sp['c'],$sp['m'],$sp['y'],$sp['k']));
				}
			}
			else if ($this->restrictColorSpace==1) {
				$sp = $this->spotColors[$this->spotColorIDs[$c[1]]];
				$c = $this->cmyk2gray(array(4,$sp['c'],$sp['m'],$sp['y'],$sp['k']));
			}
		}
		else if ($c[0]==3) {	// RGB
			if ($this->PDFX || ($this->PDFA && $this->restrictColorSpace==3)) {
				if (($this->PDFA && !$this->PDFAauto) || ($this->PDFX && !$this->PDFXauto)) { $this->PDFAXwarnings[] = "RGB color specified '".$color."' (converted to CMYK)"; }
				$c = $this->rgb2cmyk($c);
			}
			else if ($this->restrictColorSpace==1) { $c = $this->rgb2gray($c); }
			else if ($this->restrictColorSpace==3) { $c = $this->rgb2cmyk($c); }
		}
		else if ($c[0]==4) {	// CMYK
			if ($this->PDFA && $this->restrictColorSpace!=3) {
				if ($this->PDFA && !$this->PDFAauto) { $this->PDFAXwarnings[] = "CMYK color specified '".$color."' (converted to RGB)"; }
				$c = $this->cmyk2rgb($c);
			}
			else if ($this->restrictColorSpace==1) { $c = $this->cmyk2gray($c); }
			else if ($this->restrictColorSpace==2) { $c = $this->cmyk2rgb($c); }
		}
		else if ($c[0]==5) {	// RGBa
			if ($this->PDFX || ($this->PDFA && $this->restrictColorSpace==3)) {
				if (($this->PDFA && !$this->PDFAauto) || ($this->PDFX && !$this->PDFXauto)) { $this->PDFAXwarnings[] = "RGB color with transparency specified '".$color."' (converted to CMYK without transparency)"; }
				$c = $this->rgb2cmyk($c);
				$c = array(4, $c[1], $c[2], $c[3], $c[4]);
			}
			else if ($this->PDFA && $this->restrictColorSpace!=3) {
				if (!$this->PDFAauto) { $this->PDFAXwarnings[] = "RGB color with transparency specified '".$color."' (converted to RGB without transparency)"; }
				$c = $this->rgb2cmyk($c);
				$c = array(4, $c[1], $c[2], $c[3], $c[4]);
			}
			else if ($this->restrictColorSpace==1) { $c = $this->rgb2gray($c); }
			else if ($this->restrictColorSpace==3) { $c = $this->rgb2cmyk($c); }
		}
		else if ($c[0]==6) {	// CMYKa
			if ($this->PDFA && $this->restrictColorSpace!=3) {
				if (($this->PDFA && !$this->PDFAauto) || ($this->PDFX && !$this->PDFXauto)) { $this->PDFAXwarnings[] = "CMYK color with transparency specified '".$color."' (converted to RGB without transparency)"; }
				$c = $this->cmyk2rgb($c);
				$c = array(3, $c[1], $c[2], $c[3]);
			}
			else if ($this->PDFX || ($this->PDFA && $this->restrictColorSpace==3)) {
				if (($this->PDFA && !$this->PDFAauto) || ($this->PDFX && !$this->PDFXauto)) { $this->PDFAXwarnings[] = "CMYK color with transparency specified '".$color."' (converted to CMYK without transparency)"; }
				$c = $this->cmyk2rgb($c);
				$c = array(3, $c[1], $c[2], $c[3]);
			}
			else if ($this->restrictColorSpace==1) { $c = $this->cmyk2gray($c); }
			else if ($this->restrictColorSpace==2) { $c = $this->cmyk2rgb($c); }
		}
	}
	if (is_array($c)) {
		$c = array_pad($c, 6, 0);
		$cstr = pack("a1ccccc", $c[0], ($c[1] & 0xFF), ($c[2] & 0xFF), ($c[3] & 0xFF), ($c[4] & 0xFF), ($c[5] & 0xFF) );
	}
	return $cstr;
}

function rgb2gray($c) {
	if (isset($c[4])) { return array(1,(($c[1] * .21) + ($c[2] * .71) + ($c[3] * .07)), ord(1), $c[4]); }
	else { return array(1,(($c[1] * .21) + ($c[2] * .71) + ($c[3] * .07))); }
}

function cmyk2gray($c) {
	$rgb = $this->cmyk2rgb($c);
	return $this->rgb2gray($rgb);
}

function rgb2cmyk($c) {
	$cyan = 1 - ($c[1] / 255);
	$magenta = 1 - ($c[2] / 255);
	$yellow = 1 - ($c[3] / 255);
	$min = min($cyan, $magenta, $yellow);

	if ($min == 1) {
		if ($c[0]==5) { return array (6,100,100,100,100, $c[4]); }
		else { return array (4,100,100,100,100); }
		// For K-Black
		//if ($c[0]==5) { return array (6,0,0,0,100, $c[4]); }
		//else { return array (4,0,0,0,100); }
	}
	$K = $min;
	$black = 1 - $K;
	if ($c[0]==5) { return array (6,($cyan-$K)*100/$black, ($magenta-$K)*100/$black, ($yellow-$K)*100/$black, $K*100, $c[4]); }
	else { return array (4,($cyan-$K)*100/$black, ($magenta-$K)*100/$black, ($yellow-$K)*100/$black, $K*100); }
}


function cmyk2rgb($c) {
	$rgb = array();
	$colors = 255 - ($c[4]*2.55);
	$rgb[0] = intval($colors * (255 - ($c[1]*2.55))/255);
	$rgb[1] = intval($colors * (255 - ($c[2]*2.55))/255);
	$rgb[2] = intval($colors * (255 - ($c[3]*2.55))/255);
	if ($c[0]==6) { return array (5,$rgb[0],$rgb[1],$rgb[2], $c[5]); }
	else { return array (3,$rgb[0],$rgb[1],$rgb[2]); }
}

function rgb2hsl($var_r, $var_g, $var_b) {
    $var_min = min($var_r,$var_g,$var_b);
    $var_max = max($var_r,$var_g,$var_b);
    $del_max = $var_max - $var_min;
    $l = ($var_max + $var_min) / 2;
    if ($del_max == 0) {
            $h = 0;
            $s = 0;
    }
    else {
            if ($l < 0.5) { $s = $del_max / ($var_max + $var_min); }
            else { $s = $del_max / (2 - $var_max - $var_min); }
            $del_r = ((($var_max - $var_r) / 6) + ($del_max / 2)) / $del_max;
            $del_g = ((($var_max - $var_g) / 6) + ($del_max / 2)) / $del_max;
            $del_b = ((($var_max - $var_b) / 6) + ($del_max / 2)) / $del_max;
            if ($var_r == $var_max) { $h = $del_b - $del_g; }
            elseif ($var_g == $var_max)  { $h = (1 / 3) + $del_r - $del_b; }
            elseif ($var_b == $var_max)  { $h = (2 / 3) + $del_g - $del_r; };
            if ($h < 0) { $h += 1; }
            if ($h > 1) { $h -= 1; }
    }
    return array($h,$s,$l);
}


function hsl2rgb($h2,$s2,$l2) {
	// Input is HSL value of complementary colour, held in $h2, $s, $l as fractions of 1
	// Output is RGB in normal 255 255 255 format, held in $r, $g, $b
	// Hue is converted using function hue_2_rgb, shown at the end of this code
	if ($s2 == 0) {
		$r = $l2 * 255;
		$g = $l2 * 255;
		$b = $l2 * 255;
	}
	else {
		if ($l2 < 0.5) { $var_2 = $l2 * (1 + $s2); }
		else { $var_2 = ($l2 + $s2) - ($s2 * $l2); }
		$var_1 = 2 * $l2 - $var_2;
		$r = round(255 * $this->hue_2_rgb($var_1,$var_2,$h2 + (1 / 3)));
		$g = round(255 * $this->hue_2_rgb($var_1,$var_2,$h2));
		$b = round(255 * $this->hue_2_rgb($var_1,$var_2,$h2 - (1 / 3)));
	}
	return array($r,$g,$b);
}

function hue_2_rgb($v1,$v2,$vh) {
	// Function to convert hue to RGB, called from above
	if ($vh < 0) { $vh += 1; };
	if ($vh > 1) { $vh -= 1; };
	if ((6 * $vh) < 1) { return ($v1 + ($v2 - $v1) * 6 * $vh); };
	if ((2 * $vh) < 1) { return ($v2); };
	if ((3 * $vh) < 2) { return ($v1 + ($v2 - $v1) * ((2 / 3 - $vh) * 6)); };
	return ($v1);
}

function _invertColor($cor) {
	if ($cor[0]==3 || $cor[0]==5) {	// RGB
		return array(3, (255-$cor[1]), (255-$cor[2]), (255-$cor[3]));
	}
	else if ($cor[0]==4 || $cor[0]==6) {	// CMYK
		return array(4, (100-$cor[1]), (100-$cor[2]), (100-$cor[3]), (100-$cor[4]));
	}
	else if ($cor[0]==1) {	// Grayscale
		return array(1, (255-$cor[1]));
	}
	// Cannot cope with non-RGB colors at present
	die('Error in _invertColor - trying to invert non-RGB color');
}

function _colAtoString($cor) {
	$s = '';
	if ($cor{0}==1) $s = 'rgb('.ord($cor{1}).','.ord($cor{1}).','.ord($cor{1}).')';
	else if ($cor{0}==2) $s = 'spot('.ord($cor{1}).','.ord($cor{2}).')';		// SPOT COLOR
	else if ($cor{0}==3) $s = 'rgb('.ord($cor{1}).','.ord($cor{2}).','.ord($cor{3}).')';
	else if ($cor{0}==4) $s = 'cmyk('.ord($cor{1}).','.ord($cor{2}).','.ord($cor{3}).','.ord($cor{4}).')';
	else if ($cor{0}==5) $s = 'rgba('.ord($cor{1}).','.ord($cor{2}).','.ord($cor{3}).','.sprintf('%0.2F',ord($cor{4})/100).')';
	else if ($cor{0}==6) $s = 'cmyka('.ord($cor{1}).','.ord($cor{2}).','.ord($cor{3}).','.ord($cor{4}).','.sprintf('%0.2F',ord($cor{5})/100).')';
	return $s;
}

function ConvertSize($size=5,$maxsize=0,$fontsize=false,$usefontsize=true){
// usefontsize - setfalse for e.g. margins - will ignore fontsize for % values
// Depends of maxsize value to make % work properly. Usually maxsize == pagewidth
// For text $maxsize = Fontsize
// Setting e.g. margin % will use maxsize (pagewidth) and em will use fontsize
  //Identify size (remember: we are using 'mm' units here)
	$size = trim(strtolower($size));

  if ( $size == 'thin' ) $size = 1*(25.4/$this->dpi); //1 pixel width for table borders
  elseif ( stristr($size,'px') ) $size *= (25.4/$this->dpi); //pixels
  elseif ( stristr($size,'cm') ) $size *= 10; //centimeters
  elseif ( stristr($size,'mm') ) $size += 0; //millimeters
  elseif ( stristr($size,'pt') ) $size *= 25.4/72; //72 pts/inch
  elseif ( stristr($size,'em') ) {
  	$size += 0; //make "0.83em" become simply "0.83"
	if ($fontsize) { $size *= $fontsize; }
	else { $size *= $maxsize; }
  }
  elseif ( stristr($size,'%') ) {
  	$size += 0; //make "90%" become simply "90"
	if ($fontsize && $usefontsize) { $size *= $fontsize/100; }
	else { $size *= $maxsize/100; }
  }
  elseif ( stristr($size,'in') ) $size *= 25.4; //inches
  elseif ( stristr($size,'pc') ) $size *= 38.1/9; //PostScript picas
  elseif ( stristr($size,'ex') ) {	// Approximates "ex" as half of font height
  	$size += 0; //make "3.5ex" become simply "3.5"
	if ($fontsize) { $size *= $fontsize/2; }
	else { $size *= $maxsize/2; }
  }
  elseif ( $size == 'medium' ) $size = 3*(25.4/$this->dpi); //3 pixel width for table borders
  elseif ( $size == 'thick' ) $size = 5*(25.4/$this->dpi); //5 pixel width for table borders
  elseif ($size == 'xx-small') {
	if ($fontsize) { $size *= $fontsize*0.7; }
	else { $size *= $maxsize*0.7; }
  }
  elseif ($size == 'x-small') {
	if ($fontsize) { $size *= $fontsize*0.77; }
	else { $size *= $maxsize*0.77; }
  }
  elseif ($size == 'small') {
	if ($fontsize) { $size *= $fontsize*0.86; }
	else { $size *= $maxsize*0.86; }
  }
  elseif ($size == 'medium') {
	if ($fontsize) { $size *= $fontsize; }
	else { $size *= $maxsize; }
  }
  elseif ($size == 'large') {
	if ($fontsize) { $size *= $fontsize*1.2; }
	else { $size *= $maxsize*1.2; }
  }
  elseif ($size == 'x-large') {
	if ($fontsize) { $size *= $fontsize*1.5; }
	else { $size *= $maxsize*1.5; }
  }
  elseif ($size == 'xx-large') {
	if ($fontsize) { $size *= $fontsize*2; }
	else { $size *= $maxsize*2; }
  }
  else $size *= (25.4/$this->dpi); //nothing == px
  
  return $size;
}


function lesser_entity_decode($html) {
  //supports the most used entity codes (only does ascii safe characters)
 	//$html = str_replace("&nbsp;"," ",$html);	// mPDF 5.3.59
 	$html = str_replace("&lt;","<",$html);
 	$html = str_replace("&gt;",">",$html);

 	$html = str_replace("&apos;","'",$html);
 	$html = str_replace("&quot;",'"',$html);
 	$html = str_replace("&amp;","&",$html);
	return $html;
}

function AdjustHTML($html, $tabSpaces=8) {
	//Try to make the html text more manageable (turning it into XHTML)
	if (PHP_VERSION_ID < 50307) {
		if (strlen($html) > 100000) {
			if (PHP_VERSION_ID < 50200) $this->Error("The HTML code is more than 100,000 characters. You should use WriteHTML() with smaller string lengths.");
			else ini_set("pcre.backtrack_limit","1000000");
		}
	}


	preg_match_all("/(<svg.*?<\/svg>)/si", $html, $svgi);
	if (count($svgi[0])) {
		for($i=0;$i<count($svgi[0]);$i++) {
			$file = _MPDF_TEMP_PATH.'_tempSVG'.RAND(1,10000).'_'.$i.'.svg';
			//Save to local file
			file_put_contents($file, $svgi[0][$i]);
			$html = str_replace($svgi[0][$i], '<img src="'.$file.'" />', $html); 	// mPDF 5.5.18
		}
	}

	//Remove javascript code from HTML (should not appear in the PDF file)
	$html = preg_replace('/<script.*?<\/script>/is','',$html);

	//Remove special comments
	$html = preg_replace('/<!--mpdf/i','',$html);
	$html = preg_replace('/mpdf-->/i','',$html);

	//Remove comments from HTML (should not appear in the PDF file)
	$html = preg_replace('/<!--.*?-->/s','',$html);

	$html = preg_replace('/\f/','',$html); //replace formfeed by nothing
	$html = preg_replace('/\r/','',$html); //replace carriage return by nothing

	// Well formed XHTML end tags
	$html = preg_replace('/<(br|hr)\/>/i',"<\\1 />",$html);
	// Get rid of empty <thead></thead>
	$html = preg_replace('/<thead>\s*<\/thead>/i','',$html);
	$html = preg_replace('/<tfoot>\s*<\/tfoot>/i','',$html);
	$html = preg_replace('/<table[^>]*>\s*<\/table>/i','',$html);
	$html = preg_replace('/<tr>\s*<\/tr>/i','',$html);

	// Remove spaces at end of table cells
	$html = preg_replace("/[ \n\r]+<\/t(d|h)/",'</t\\1',$html);		// mPDF 5.5.09

	$html = preg_replace("/[ ]*<dottab\s*[\/]*>[ ]*/",'<dottab />',$html);

	// Concatenates any Substitute characters from symbols/dingbats
	$html = str_replace('</tts><tts>','|',$html);
	$html = str_replace('</ttz><ttz>','|',$html);
	$html = str_replace('</tta><tta>','|',$html);

	$html = preg_replace('/<br \/>\s*/is',"<br />",$html);

	// Preserve '\n's in content between the tags <pre> and </pre>
	if (preg_match('/<pre/',$html)) {
		$html_a = preg_split('/(\<\/?pre[^\>]*\>)/', $html, -1, 2);
		$h = array();
		$c=0;
		foreach($html_a AS $s) {
			if ($c>1 && preg_match('/^<\/pre/i',$s)) { $c--; $s=preg_replace('/<\/pre/i','</innerpre',$s); }
			else if ($c>0 && preg_match('/^<pre/i',$s)) { $c++; $s=preg_replace('/<pre/i','<innerpre',$s); }
			else if (preg_match('/^<pre/i',$s)) { $c++; }
			else if (preg_match('/^<\/pre/i',$s)) { $c--; }
			array_push($h, $s);
		}
		$html = implode("", $h);
	}
	$thereispre = preg_match_all('#<pre(.*?)>(.*?)</pre>#si',$html,$temp);
	// Preserve '\n's in content between the tags <textarea> and </textarea>
	$thereistextarea = preg_match_all('#<textarea(.*?)>(.*?)</textarea>#si',$html,$temp2);
	$html = preg_replace('/[\n]/',' ',$html); //replace linefeed by spaces
	$html = preg_replace('/[\t]/',' ',$html); //replace tabs by spaces

	// Converts < to &lt; when not a tag
	$html = preg_replace('/<([^!\/a-zA-Z])/i','&lt;\\1',$html);
	$html = preg_replace("/[ ]+/",' ',$html);

	$html = preg_replace('/\/li>\s+<\/(u|o)l/i','/li></\\1l',$html);
	$html = preg_replace('/\/(u|o)l>\s+<\/li/i','/\\1l></li',$html);
	$html = preg_replace('/\/li>\s+<\/(u|o)l/i','/li></\\1l',$html);
	$html = preg_replace('/\/li>\s+<li/i','/li><li',$html);
	$html = preg_replace('/<(u|o)l([^>]*)>[ ]+/i','<\\1l\\2>',$html);
	$html = preg_replace('/[ ]+<(u|o)l/i','<\\1l',$html);

	$iterator = 0;
	while($thereispre) //Recover <pre attributes>content</pre>
	{
		$temp[2][$iterator] = preg_replace("/^([^\n\t]*?)\t/me", "stripslashes('\\1') . str_repeat(' ',  ( $tabSpaces - (mb_strlen(stripslashes('\\1')) % $tabSpaces))  )",$temp[2][$iterator]);
		$temp[2][$iterator] = preg_replace('/\t/',str_repeat(" ",$tabSpaces),$temp[2][$iterator]);

		$temp[2][$iterator] = preg_replace('/\n/',"<br />",$temp[2][$iterator]);
		$temp[2][$iterator] = str_replace('\\',"\\\\",$temp[2][$iterator]);
		$html = preg_replace('#<pre(.*?)>(.*?)</pre>#si','<erp'.$temp[1][$iterator].'>'.$temp[2][$iterator].'</erp>',$html,1);
		$thereispre--;
		$iterator++;
	}
	$iterator = 0;
	while($thereistextarea) //Recover <textarea attributes>content</textarea>
	{
		$temp2[2][$iterator] = preg_replace('/\t/',str_repeat(" ",$tabSpaces),$temp2[2][$iterator]);
		$temp2[2][$iterator] = str_replace('\\',"\\\\",$temp2[2][$iterator]);	// mPDF 5.3.88
		$html = preg_replace('#<textarea(.*?)>(.*?)</textarea>#si','<aeratxet'.$temp2[1][$iterator].'>'.trim($temp2[2][$iterator]) .'</aeratxet>',$html,1);
		$thereistextarea--;
		$iterator++;
	}
	//Restore original tag names
	$html = str_replace("<erp","<pre",$html);
	$html = str_replace("</erp>","</pre>",$html);
	$html = str_replace("<aeratxet","<textarea",$html);
	$html = str_replace("</aeratxet>","</textarea>",$html);
	$html = str_replace("</innerpre","</pre",$html);
	$html = str_replace("<innerpre","<pre",$html);

	$html = preg_replace('/<textarea([^>]*)><\/textarea>/si','<textarea\\1> </textarea>',$html);
	$html = preg_replace('/(<table[^>]*>)\s*(<caption)(.*?<\/caption>)(.*?<\/table>)/si','\\2 position="top"\\3\\1\\4\\2 position="bottom"\\3',$html);	// *TABLES*
	$html = preg_replace('/<(h[1-6])([^>]*)(>(?:(?!h[1-6]).)*?<\/\\1>\s*<table)/si','<\\1\\2 keep-with-table="1"\\3',$html);	// *TABLES*
	$html = preg_replace("/\xbb\xa4\xac/", "\n", $html);
	return $html;
}

function dec2other($num, $cp) {
	$nstr = (string) $num;
	$rnum = '';
	for ($i=0;$i<strlen($nstr);$i++) {
		if ($this->_charDefined($this->CurrentFont['cw'],$cp+intval($nstr[$i]))) { // contains arabic-indic numbers
			$rnum .= code2utf($cp+intval($nstr[$i]));
		}
		else { $rnum .= $nstr[$i]; }
	}
	return $rnum;
}

function dec2alpha($valor,$toupper="true"){
// returns a string from A-Z to AA-ZZ to AAA-ZZZ
// OBS: A = 65 ASCII TABLE VALUE
  if (($valor < 1)  || ($valor > 18278)) return "?"; //supports 'only' up to 18278
  $c1 = $c2 = $c3 = '';
  if ($valor > 702) // 3 letters (up to 18278)
    {
      $c1 = 65 + floor(($valor-703)/676);
      $c2 = 65 + floor((($valor-703)%676)/26);
      $c3 = 65 + floor((($valor-703)%676)%26);
    }
  elseif ($valor > 26) // 2 letters (up to 702)
  {
      $c1 = (64 + (int)(($valor-1) / 26));
      $c2 = (64 + (int)($valor % 26));
      if ($c2 == 64) $c2 += 26;
  }
  else // 1 letter (up to 26)
  {
      $c1 = (64 + $valor);
  }
  $alpha = chr($c1);
  if ($c2 != '') $alpha .= chr($c2);
  if ($c3 != '') $alpha .= chr($c3);
  if (!$toupper) $alpha = strtolower($alpha);
  return $alpha;
}


function dec2roman($valor,$toupper=true){
 //returns a string as a roman numeral
  $r1=$r2=$r3=$r4='';
  if (($valor >= 5000) || ($valor < 1)) return "?"; //supports 'only' up to 4999
  $aux = (int)($valor/1000);
  if ($aux!==0)
  {
    $valor %= 1000;
    while($aux!==0)
    {
    	$r1 .= "M";
    	$aux--;
    }
  }
  $aux = (int)($valor/100);
  if ($aux!==0)
  {
    $valor %= 100;
    switch($aux){
    	case 3: $r2="C";
    	case 2: $r2.="C";
    	case 1: $r2.="C"; break;
  	  case 9: $r2="CM"; break;
  	  case 8: $r2="C";
  	  case 7: $r2.="C";
    	case 6: $r2.="C";
      case 5: $r2="D".$r2; break;
      case 4: $r2="CD"; break;
      default: break;
	  }
  }
  $aux = (int)($valor/10);
  if ($aux!==0)
  {
    $valor %= 10;
    switch($aux){
    	case 3: $r3="X";
    	case 2: $r3.="X";
    	case 1: $r3.="X"; break;
    	case 9: $r3="XC"; break;
    	case 8: $r3="X";
    	case 7: $r3.="X";
  	  case 6: $r3.="X";
      case 5: $r3="L".$r3; break;
      case 4: $r3="XL"; break;
      default: break;
    }
  }
  switch($valor){
  	case 3: $r4="I";
  	case 2: $r4.="I";
  	case 1: $r4.="I"; break;
  	case 9: $r4="IX"; break;
  	case 8: $r4="I";
    case 7: $r4.="I";
    case 6: $r4.="I";
    case 5: $r4="V".$r4; break;
    case 4: $r4="IV"; break;
    default: break;
  }
  $roman = $r1.$r2.$r3.$r4;
  if (!$toupper) $roman = strtolower($roman);
  return $roman;
}


//===========================


/* ---------------------------------------------- */
/* ---------------------------------------------- */
/* ---------------------------------------------- */
/* ---------------------------------------------- */
/* ---------------------------------------------- */

// JAVASCRIPT
function _set_object_javascript ($string) {
	$this->_newobj();
	$this->_out('<<');
	$this->_out('/S /JavaScript ');
	$this->_out('/JS '.$this->_textstring($string));
	$this->_out('>>');
	$this->_out('endobj');
}

function SetJS($script) {
	$this->js = $script;
}




}//end of Class

