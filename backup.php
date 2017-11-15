<?php
echo "\nIMAGE BACKUP & ARKIV\n";



include_once "vendor/autoload.php";
use \diversen\gps;
use Intervention\Image\ImageManagerStatic as Image;
// configure with favored image driver (gd by default)
#Image::configure(array('driver' => 'imagick'));


#$image = Image::make('img/20151123-081338-2542.JPG')->resize(300, 200)
// read all existing data into an array


// read model of the camera
#$name = Image::make('img/20151123-081338-2542.JPG')->exif('Model');

#print_r($data);
#print_r($name);

#die('done');
// Example file
// $file = "vendor/diversen/gps-from-exif/failure.jpg";



# Elequent form  composer
# https://stackoverflow.com/questions/26083175/can-i-use-laravels-database-layer-standalone





#var_dump($argv);

// if( $argv[1] == 'find' && $argv[2] != null){
//     $findtext = $argv[2];
//  #echo 'Find: '.$findtext;
//     $sql = '%'.$findtext.'%';
//     $rows = R::find( 'txt' , 'txt LIKE ?', [$sql]);
    
//     if(count($rows)==1){
//         echo "\n$rows->id: [$rows->sub]\n";
//         echo $rows->txt;
//     }

//     if(count($rows) > 1){
//         foreach ($rows as $r){
//             $txt = str_replace("\n", '', $r->txt);
//             echo "\n$r->id: [$r->filename] ". substr($txt,0,50); 
//         }
//     }
// }

// $file_ext = [ "jpg","bmp","png"];
// $dirs[] = 'img';
// $filelist =  scanDir::scan($dirs, $file_ext, true);
// print_r($filelist);


##############################################################################################

require_once 'rb.php';

$db = 'sqlite'; 
$db = 'mysql'; 

if ($db == 'mysql'){
    R::setup('mysql:host=localhost;dbname=img_arkiv','root', '');
}

if ($db == 'sqlite'){
    $db_setup = 'sqlite:img_sqlite.db';
    R::setup($db_setup);
}



#backup::count();
#backup::reset();
#backup::count();
#backup::scan('*');
#backup::ls_fix();
#backup::exportfiles();
#backup::dupes();
#backup::count();
#backup::move();
#backup::get_exif();
#backup::ls();
#backup::show_exif();
#backup::rename_images(); #broken ..  fix this soon

backup::run();
#backup::fix_filenames();

#backup::is_good_filename('img\1\mapp\iphone_pix\10x00206.jpg');





// if (isset($argv)){
//     if (count($argv) > 1){
        
//         unset($argv[0]);
//         foreach ($argv as $arg) {
//             backup::$arg;
//         }
//     }else{
//         #echo "\n backup media images  db\n";
//         echo "\nImage and dup sorter  --help for more options";
//         echo "\nreset = reset database";
//         echo "\nscan = scan for new files";
//         echo "\ndupes = find dupes in database";
//         echo "\nupload = upload files to backup";
//         echo "\nimport = backup into internal database";
//         echo "\nexport = regenerte files reviously imported to internal database";
//     }
// }


// print_r(hash_algos());
// foreach (hash_algos() as $key => $algo) {

//     echo "\n$algo: ". $h = hash_file($algo, 'arkiv\2008\080309\080309_132222_2060.jpg').' Length: '.strlen($h);
// }


class backup {

    /**

        Photo backup script (work in progress)
        IMAGE BACKUP & ARKIV WITH DUP CHECK (MD5)

        Idea is to dump all your pics from a SD card or iphone export in a a dir or in the arkiv and run the script.
        the script will detect, fix and sort the images

        TODO:   scan dirs and show rename suggestions output like
                HP9394374.JPG    => 171231-2359-001.jpg

                scan to import loose files form subdir argument
                -scan path/to/dir
                if regex match YYMMDD  -> suggest move to a YYMM directory 

        GOAL
        - Sort images in folders by 
            1 filedate
            2 exif data

        - rename images to dateformat (exif if possible else filedate)

        
        TODO
        - show pics in google maps  (gps extract)
        - rename files to smart format
        - optional: save/backup the files(contents) into local sqllite db (optionally copy that db to remove place/website)
        - google map plotter
        - google map path(line) tracker from  dates in images.   each dot with date/time
        - copy/backup archive to remote places (scp ssh?)
        - Future ideas: handle video & all other types of files to backup. txt
        - Future ideas: handle optional encryption
        - Future ideas: detect other remote/backup sources (array) and updated them
        - Future ideas: Set (default) read dirs and output dirs in windows/linux docuement/user/Pictures folder etc
        - Future ideas: crontab or win schedule sorting of users images

        DONE
        - save names to sqlite db
        - move files to a smart named dir (year-month?) 
        - calculate checksums in db to find dups
        
        USAGE                

        commandline arguments --help

        -s (save all files to sqlite db in same dir)
        -c copy files to remote location
        -r rename the files to a smart convention
        -d dupes . show duplicate files (same checksum)
        -h generate html gallery files

    **/



    public $file_ext;
    public $dirs;
    public $backup_dir = 'arkiv';
    public $db_type = 'mysql';


    
    #$db_mode = 'mysql';

    // $this->file_ext = array();
    // $this->$dirs     = array();


    #$file_ext          = [ 'jpg', 'bmp', 'png' ];
    #$dirs[]            = 'img';
    #$destintation_path = "sorted\\";


    
    public function __construct() {

        #not used yet
        #self::run();
        #$this->db_mode = 'mysql';
        #$this->backup_dir = 'arkiv';


    }

    public function db_setup(){
    }
    

    static function run(){ # normal full run 
        
        #self::reset();
        
        self::clean_db();
        self::scan();
        self::dupes(); # find dupes
        self::move();  # move dupes

        #self::clean_filenames(); # strip spaces, lower case.. etc




        #self::backup_to_remote_server(); # 


        #backup::exportfiles();
        #backup::rename_images(); #broken ..  fix this soon

        foreach(R::find( 'img' ) as $img){
        
            #echo "\nProcessing: $img->filename ";
            
            self::exif( $img );
            self::datetaken( $img );

        }
        
        self::sort_files(); #
        #self::sort_exif(); #
        #self::fix_filenames();  # sorts too
        echo "\nDone";

    }



    

    # remove this?
    static function clean_filenames(){ # remove spaces, and ser lower case
        
        foreach (R::findAll( 'img' ) as $img) {
            
            $destination = $img->filename;

            #$newfile     = strtolower($dupe->filename);
            #$newfile     = str_replace("\/", '_', $newfile);
            #$newfile     = "duplicates\\$newfile";

            #$filename    = self::filename_from_path($img->filename);
            #$year        = date("Y",strtotime($img->datetaken));
            #$yearmonth   = date("ym",strtotime($img->datetaken));
            #$destination = "arkiv\\$year\\$yearmonth\\$filename";
            
            $destination  = str_replace("-", '_', $destination);
            $destination  = str_replace(" ", '_', $destination);
            $destination  = strtolower($destination);

            if ( $img->filename != $destination ){
                if( self::mv($img, $img->filename, $destination) ){
                    echo "\nClean filename $img->filename -> $destination";
                }
            }


        }
    }


    
    
    public static function sort_files(){ # rename & move  all files to into archive

        function validate($input){

            $validformat = "*^[0-9]{6}_[0-9]{6}*";
            if ( preg_match($validformat, $input) ){
                return 1;
            }else{
                return 0;
            }
        }

        function datemapp($input){

            $validformat = "*^[0-9]{6}$*";
            if ( preg_match($validformat, $input) ){
                return 1;
            }else{
                return 0;
            }
        }

        function dir_valid($input){

            $validformat = "*^[0-9]{6}_*";
            if ( preg_match($validformat, $input) ){
                return 1;
            }else{
                return 0;
            }
        }

        $backup_dir = 'arkiv';


        foreach (R::findAll( 'img' ) as $img) {  

            $filename       = self::filename_from_path($img->filename);
            $destination    = $img->filename;

            $exploded       = explode('.', $filename);
            $ext            = $exploded[count($exploded)-1];

            #org dir
            $dirs           = explode('\\', $img->filename_original);
            $dir_org        = $dirs[count($dirs)-2];

            #current dir
            $dirs           = explode('\\', $img->filename);
            $dir_now        = $dirs[count($dirs)-2];



            # optional reuse old pre dir from import
            if( datemapp($dir_now) ){  # boring dir

                $new = preg_replace("*^[0-9]{6}*", '', $dir_org );
                $new = $dir_now.'_'.$new;
                $new = str_replace('__', '_', $new); 

                if ( preg_match("*^[0-9]{6}_$*", $new) ){ # same shit, no diff
                    unset($new);
                }
            }





            if ( $img->datetaken == '' || is_null($img->datetaken) ) {  # No EXIF

                $destination = date("ymd_Hi",strtotime($img->filemtime))."_$img->crc.$ext";

                $Y        = date("Y",  strtotime($img->filemtime));
                $ymd      = date("ymd",strtotime($img->filemtime));



                if (isset($new)){
                    $ymd = $new;
                }

                if ( dir_valid($dir_now) ){
                    $dir = $dir_now;
                }else{
                    $dir = $ymd;
                }

                $destination = $backup_dir."\\$Y\\$dir\\$destination";
                $destination = strtolower($destination);
                $msg = 'date';
            }

            if ($img->datetaken != '' || !is_null($img->datetaken) ){  #  exif   rename to filedate

                $destination = date("ymd_Hi",strtotime($img->datetaken))."_$img->crc.$ext";


                $Y        = date("Y",  strtotime($img->datetaken));
                $ymd      = date("ymd",strtotime($img->datetaken));

                if (isset($new)){
                    $ymd = $new;
                }

                if ( dir_valid($dir_now) ){
                    $dir = $dir_now;
                }else{
                    $dir = $ymd;
                }


                $destination = $backup_dir."\\$Y\\$dir\\$destination";
                $destination = strtolower($destination);
                $msg = 'exif';
            }

            unset($new);


            if ($img->filename != $destination){

                if (self::mv($img,$img->filename,$destination)){
                    echo "\nSorting [$msg]: $img->filename -> $destination";
                    $img->filename = $destination;
                    R::store($img);
                }
            }

        } 
    }

    
    public static function mv($img,$src,$dst){

        if ($src != $dst){

            if (is_file($src)){
                @mkdir(dirname($dst), 0777, true);

                #echo "\nMoving: $img->filename -> $destination  final sort";

                if (!rename($src, $dst)) {
                    echo "Failed!\nfailed to move $src to $dst";
                    return 0;
                }else{
                    $img->filename = $dst;
                    R::store( $img );
                    return 1;
                }
            }else{
                #return 'file is allready moved there';
                return 0;
            }
        }
        return 0;

    }


    static function move(){ #move dupes and delete db rows flagged as dupe by ::dupes method

            
        ## Action for files flagged as dupes

        $dupes = R::findAll( 'img' , ' dup = 1 ' );
        
        foreach ($dupes as $dupe) {
            $newfile = strtolower($dupe->filename);
            $newfile = str_replace("\/", '_', $newfile);
            $newfile = str_replace("\\", '_', $newfile);
            $newfile = "duplicates\\$newfile";

            if (is_file($dupe->filename)){
                @mkdir(dirname($newfile), 0777, true);

                echo "\nMoving duplicate: $dupe->filename => $newfile ..";

                if (!rename($dupe->filename, $newfile)) {
                    echo "Failed!\nfailed to move $dupe->filename to $newfile";
                }else{
                    echo "done.";
                    #$dupe->filename = $newfile;
                    #R::store( $dupe );
                    R::trash( $dupe );
                }
            }
        }


        
        ## Action for files flagged for moving
        # refactor this later to own method

        $move_list = R::findAll( 'img' , ' move = 1 ' );
        foreach ($move_list as $f) {
            $newfile = strtolower($f->filename);
            $newfile = str_replace("\/", '_', $newfile);
            $newfile = str_replace("\\", '_', $newfile);
            $newfile = "duplicates\\$newfile";

            if (is_file($f->filename)){
                @mkdir(dirname($newfile), 0777, true);

                echo "\nMoving: $f->filename => $newfile ..";

                if (!rename($f->filename, $newfile)) {
                    echo "Failed!\nfailed to move $f->filename to $newfile";
                }else{
                    echo "done.";
                    R::trash( $dupe );
                }
            }
        }
    }
    


    static function clean_db() {
        
        foreach ( R::find( 'img' ) as $img) {
            
            if (!file_exists($img->filename)) { 
                echo "\nLost: $img->filename";
                R::trash( $img );  # remove entry from db
            }
         }
    }


    static function scan($type = null) {
        
        #maybe recheck if all the files in db still exist on the disk??
        // $db_rows = R::getAll( 'SELECT  filename  from img' );
        
        // if ($db_rows){
        //     foreach ($db_rows as $row) {
        //         $files_db[] = $row['filename'];
        //     }
        // }

        #$db_rows = R::getAll( 'SELECT  filename  from img' );
        
        $files_db = array();
        foreach (R::getAll( 'SELECT  filename  from img' ) as $row) {
            $files_db[] = $row['filename'];
        }


        $image_extensions = [ 'jpg', 'bmp', 'png', 'tif', 'tiff', 'jpeg' ];
        $video_extensions = [ 'mov', 'mp4','wmv' ];
        $audio_extensions = [ 'mp3', 'wav', 'ogg' ];



        $file_ext = [ 'jpg', 'bmp', 'png' ];
        $dirs     = [ 'img','arkiv'];
        $files    = scanDir::scan($dirs, $file_ext, true); # dirs, file extensions, recurse
        $files    = array_unique($files); # scanDir lists dublicate files in subdirs

        foreach ( $files as $filename){


            if (file_exists($filename) && is_file($filename)) {


                if (!in_array($filename, $files_db)){

                    #echo "$filename was last accessed: " . date("F d Y H:i:s.", fileatime($filename));
                    #$filemtime =  date("Y-m-d H:i:s", filemtime($filename));
                    #echo "Saving to db: $filename size " . filesize($filename) . " $date \n";
                    #$filename_utf8 = utf8_decode($filename);;


                    
                    #$filename = utf8_encode($filename);
                    #$filename = $filename_utf8;
                    #$filename = utf8_encode($filename); 
                    echo "\nFound: $filename ";
                    
                    $filecontents  = file_get_contents($filename);  #refactor to calc hash in own method
                    $md5           = md5($filecontents);   
                    echo $md5;

                    $crc           = hash_file('crc32b', $filename);
                    echo  " ".$crc;

                    $r             = R::dispense( 'img' );
                    $r->md5        = $md5;
                    $r->crc        = $crc;
                    $r->exif       = '';
                    $r->gps        = '';
                    $r->dup        = '';
                    $r->hasexif    = ''; #default unset until checked
                    $r->md5        = $md5;
                    #$r->hash        = hash_file('crc32b', $filename);
                    
                    $r->filemtime  = date("Y-m-d H:i:s", filemtime($filename));
                    #$r->datetaken  = '';
                    $r->filename_original   = $filename;
                    $r->filename     = $filename;
                    $r->created_at = date("Y-m-d H:i:s"); #found at
                    #$r->updated_at = NULL;
                    #$r->deleted_at = NULL;
                    R::store($r);
                }else{
                    #echo "\nSkipping: $filename";
                    #echo "."; flush();
                }
            }
        }
    }

    static function exif($img = null) {


        /**

            Try to extract Exif data 

        **/

        ini_set('memory_limit', '1024M'); // or you could use 1G

        
        
        if ( $img->hasexif == '' ){

            $img->hasexif = 0; # No exif until proven otherwise below
            R::store( $img );

            if ( strlen($img->exif) < 3 ){


                $exif = exif_read_data($img->filename, 0, true);
                
                if (is_array($exif)){
                    if ( count($exif) > 1 ){
                        #echo "OK";
                        $img->hasexif = 1;
                        $img->exif = json_encode($exif);
                        R::store($img);

                    }else{
                        $img->exif = '';
                        R::store($img);
                    }

                }else{
                    $img->exif = '';
                    R::store($img);
                }
            }else{
                $img->exif = '';
                R::store($img);
            }
        }


        // try {
        //     Image::make($img->filename)->exif();
        //     $exif = Image::make($img->filename)->exif();
        //     $exif  = json_encode($exif);
        //     $img->exif = $exif;
        //     R::store($img);
        // } catch (Exception $e) {
        //     echo 'Caught exception: ',  $e->getMessage(), "\n";
        // } finally{
        //     echo "\nfinally.. $img->filename";
        // }



        // $g = new gps();
        // $gps = $g->getGpsPosition($filename);
        // if (empty($gps)) {
        //     #die('Could not get GPS position' . PHP_EOL);
        //     #echo "no gps info";
        //     $gpsinfo = '';
        // }else{
        //     #print_r($gps);
        //     #echo "<img src=\"$r->filename\" width=100 height=75>";
        //     #echo $gmap = $g->getGmap($gps['latitude'], $gps['longitude'], 600, 350);
        //     $gpsinfo =  json_encode($gps);
        // }
    }


    static function gps_stuff(){

        /**
            $g = new gps();

            // Get GPS position
            $file = "vendor/diversen/gps-from-exif/example.jpg";
            $gps = $g->getGpsPosition($file);
            if (empty($gps)) {
                die('Could not get GPS position' . PHP_EOL);
            }

            print_r($gps);

            // Get a google map
            echo $gmap = $g->getGmap($gps['latitude'], $gps['longitude'], 600, 350);
            **/


    }

    #list all
    static function ls(){
        


        foreach(R::find( 'img' ) as $r){
            #echo "\n$r->id: [$r->filename] ". substr($r->txt,0,20);
            #print_r(json_decode($r->exif));
            #if(isset($exif['model'])){
            #if( property_exists('exif', 'model'))
            if( is_object(json_decode($r->exif)) ) {
                echo "\n------------------------------------";
                echo "\n$r->id: [$r->filename]\n ";#. date("Y-m-d H:i:s",$r->filedate);
                #var_dump(json_decode($r->exif));


                $json_pretty = json_encode(json_decode($r->exif), JSON_PRETTY_PRINT);
                echo $json_pretty;
                
            }

        }
    }



    public static function is_good_filename($filename){
        $filename_parts = explode('\\', $filename);
        $name = $filename_parts[count($filename_parts)-1];

        $dotparts = explode('.', $filename);
        $ext = $dotparts[count($dotparts)-1];


        $first_six = substr($name, 0,6);

        $valid = "*[0-9]{6}*";
        if ( preg_match($valid, $first_six ) ){
            #echo "\nOk $first_six is valid for $first_six";

            $from = strtotime("1900-00-00");
            $now  = time();
            $date = strtotime($first_six);
            
            


            // $two   = substr($first_six,0,2);
            // $four  = substr($first_six,0,4);
            // if ($two == '19'  or $two  == '20'){
            //     #if( $four > )    
            //     # ÄH ORKAR INTE TÄNKA? ere rätt approach ens?


            // }
            #if ($now - $date > 60*60*24*365*1000){ #100 år bakåt
             #   return 1;
            #} 

            return 1;
        }else{
            #echo "\nnot $first_six wont match $valid";
            return 0;
        }
    }


    public static function filename_from_path($filename_with_path){
        $parts = explode('\\', $filename_with_path);  # also make compatible for  windows + linux  slash direction
        $filename = $parts[count($parts)-1]; #last part
        return $filename;
    }


    static function datetaken($img){  

        /**
         
         Detect best date-taken from exif data and store in db field datetaken

         **/


        if ( $img->exif != '' && $img->datetaken == ''){

            if ( is_array( $exif_array = json_decode($img->exif, true)) ){
                $exif_array = json_decode($img->exif, true);
                #print_r($exif_array);
                #if (isset($))

                $codes ="";
                foreach ($exif_array as $key => $value) {
                    $codes .= "$key ";
                }

                if (isset($exif_array['FILE']['FileDateTime'])){

                    
                    #$codes .= date("ymd_His",$exif_array['FILE']['FileDateTime']);
                    #$FILE_FileDateTime = date("ymd_His",$exif_array['FILE']['FileDateTime']);
                    $img->datetaken = date("Y-m-d H:i:s",$exif_array['FILE']['FileDateTime']);
                    R::store( $img );
                    return 1;
                }
            }
        }
        return 0; 

    }



    static function rename_images(){

        foreach(R::find( 'img' ) as $img) {

            $new_name = strtolower($img->filename); #lower case
            $new_name = str_replace(' ', '_', $new_name); # space to underscore

            $dotparts = explode('.', $img->filename);
            $ext = $dotparts[count($dotparts)-1];

            $move = 0;
            $msg = "bad";
            $codes ="";

            if (self::is_good_filename($img->filename)){
                $move = 1;
                $new_name = strtolower($img->filename); #lower case
                $new_name = str_replace(' ', '', $new_name);
                $msg = "org is ok";

            }else{


                #todo fix this part!
                if ( $img->exifdate ){


                    $dir      = substr($FILE_FileDateTime,0,4).'/';
                    $new_name = $dir . $FILE_FileDateTime.'_'.$img->id.".$ext";
                    $new_name = strtolower($new_name); #lower case
                    $new_name = str_replace(' ', '', $new_name);

                if ( self::is_good_filename( $new_name) ) {
                    $move = 1;
                    $msg = "using FILE FileDateTime";
                }



                    // if (isset($exif_array['EXIF']['DateTimeOriginal'])){
                    //     $codes .=  ' xdate-> '. date("ymd_H:i.s",strtotime( $exif_array['EXIF']['DateTimeOriginal']) );
                    //     $exif_date  = date("ymd_H:i.s",strtotime( $exif_array['EXIF']['DateTimeOriginal']);
                    // }
                
                }else{
                    $msg = "No exif data";
                }

            }

            if ($move == 1){
                $img->destination  = $new_name;
                $img->move  = 1;
                R::store($img);
            }
            

            echo "\n$img->filename => $new_name [move:$move $msg]  $codes";
            
        }
    }

    static function make_google_maps() {
        #Google maps html output
            // $g = new gps();
            // $gps = $g->getGpsPosition($r->filename);
            // if (empty($gps)) {
            //     #die('Could not get GPS position' . PHP_EOL);
            //     echo "no gps info";
            // }else{
            //     print_r($gps);
            //     echo "<img src=\"$r->filename\" width=100 height=75>";
            //     echo $gmap = $g->getGmap($gps['latitude'], $gps['longitude'], 600, 350);
            // }
    }

    static function exportfiles(){

        foreach(R::find( 'img' ) as $r){
            echo "\n$r->id: [$r->filename]";
            file_put_contents('./output/'.$r->filename, $r->filecontents);
        }
    }

    // static function wipe(){ # not used anymore   see reset 
        
    //     $table = 'img';
    //     R::wipe($table);
    // }

    // static function nuke(){ # not used   see reset 
        
    //     $table = 'img';
    //     R::wipe($table);
    // }

    static function reset(){  # truncate the database
        
        $table = 'img';
        
        R::nuke($table);

        $new = R::dispense( $table );
        $new->filename = 'initial';
        $new->md5      = 'md5';
        $new->filedate = '';

        #$r->txt = $filecontents;
        #$r->filecontents = $filecontents;@
        $new->gps        = '';
        
        $new->exif       = '';
        $new->created_at = strtotime(date("Y-m-d H:i:s"));
        $new->updated_at = '';
        $new->deleted_at = '';
        $id = R::store($new);
        echo "\nReset of database done";

    }

    


    static function dupes() { # FLAG duplicate images in db (for removal by other method)

        #$rows = R::getAll( 'SELECT  COUNT(md5) as cnt, *  from img group by md5 having cnt >1 order by cnt desc' ); # not working with mysql only sqlite
        $rows = R::getAll( 'SELECT  md5,COUNT(md5) as cnt  from img group by md5 having cnt >1 order by cnt desc' );
        if ($rows){

            foreach ($rows as $row) {
                extract($row);
                #echo "\nDUP: $filename (id:$id)  $cnt dupes $md5";
                $dup_hashes[] = $md5;
            }
            
            foreach ($dup_hashes as $hash) {
                $sql  = "SELECT  * from img where md5 = '$hash' ";
                $rows = R::getAll( $sql );
                $i = 0;
                echo "\n-[dupes]-----------------------------------------------------------------";
                foreach ($rows as $row) {
                    extract($row);
                    echo "\n$filename  [id:$id]";
                    if($i>0){
                        $ids_to_move[] = $id; #leave the first id as the original 
                        echo "  <- will move this copy";
                    }
                    $i++;
                }
                echo "\n-------------------------------------------------------------------------\n";
                $array_of_ids[] = $id; # all with the same hash

            }

            $ids_comma_seperated = implode(',', $ids_to_move);
            $sql  = "UPDATE img SET dup='1' WHERE id IN ($ids_comma_seperated) ";
            R::exec( $sql  ); # flag dupes in db
        }else{
            #echo "\nno dupes";
        }
    }

    public static function stripText($text, $separator = '-'){
        $bad = array(
          'À','à','Á','á','Â','â','Ã','ã','Ä','ä','Å','å','Ă','ă','Ą','ą',
          'Ć','ć','Č','č','Ç','ç',
          'Ď','ď','Đ','đ',
          'È','è','É','é','Ê','ê','Ë','ë','Ě','ě','Ę','ę',
          'Ğ','ğ',
          'Ì','ì','Í','í','Î','î','Ï','ï',
          'Ĺ','ĺ','Ľ','ľ','Ł','ł',
          'Ñ','ñ','Ň','ň','Ń','ń',
          'Ò','ò','Ó','ó','Ô','ô','Õ','õ','Ö','ö','Ø','ø','ő',
          'Ř','ř','Ŕ','ŕ',
          'Š','š','Ş','ş','Ś','ś',
          'Ť','ť','Ť','ť','Ţ','ţ',
          'Ù','ù','Ú','ú','Û','û','Ü','ü','Ů','ů',
          'Ÿ','ÿ','ý','Ý',
          'Ž','ž','Ź','ź','Ż','ż',
          'Þ','þ','Ð','ð','ß','Œ','œ','Æ','æ','µ',
          '”','“','‘','’',"'","\n","\r",'_');
            
          $good = array(
          'A','a','A','a','A','a','A','a','Ae','ae','A','a','A','a','A','a',
          'C','c','C','c','C','c',
          'D','d','D','d',
          'E','e','E','e','E','e','E','e','E','e','E','e',
          'G','g',
          'I','i','I','i','I','i','I','i',
          'L','l','L','l','L','l',
          'N','n','N','n','N','n',
          'O','o','O','o','O','o','O','o','Oe','oe','O','o','o',
          'R','r','R','r',
          'S','s','S','s','S','s',
          'T','t','T','t','T','t',
          'U','u','U','u','U','u','Ue','ue','U','u',
          'Y','y','Y','y',
          'Z','z','Z','z','Z','z',
          'TH','th','DH','dh','ss','OE','oe','AE','ae','u',
          '','','','','','','','-');
            
          // convert special characters
          $text = str_replace($bad, $good, $text);
            
          // convert special characters
          $text = utf8_decode($text);
          $text = htmlentities($text);
          #$text = preg_replace('/&([a-zA-Z])(uml|acute|grave|circ|tilde);/', '$1', $text);
          $text = html_entity_decode($text);
          
          $text = strtolower($text);
            
          // strip all non word chars
          #$text = preg_replace('/\W/', ' ', $text);
            
          // replace all white space sections with a separator
          $text = preg_replace('/\ +/', $separator, $text);
        
          // trim separators
          $text = trim($text, $separator);
          //$text = preg_replace('/\-$/', '', $text);
          //$text = preg_replace('/^\-/', '', $text);
              
          return $text;
    }



}









class scanDir {
    static private $directories, $files, $ext_filter, $recursive;

    // ----------------------------------------------------------------------------------------------
    // scan(dirpath::string|array, extensions::string|array, recursive::true|false)
    static public function scan(){
        // Initialize defaults
        self::$recursive = false;
        self::$directories = array();
        self::$files = array();
        self::$ext_filter = false;

        // Check we have minimum parameters
        if(!$args = func_get_args()){
            die("Must provide a path string or array of path strings");
        }
        if(gettype($args[0]) != "string" && gettype($args[0]) != "array"){
            die("Must provide a path string or array of path strings");
        }

        // Check if recursive scan | default action: no sub-directories
        if(isset($args[2]) && $args[2] == true){self::$recursive = true;}

        // Was a filter on file extensions included? | default action: return all file types
        if(isset($args[1])){
            if(gettype($args[1]) == "array"){self::$ext_filter = array_map('strtolower', $args[1]);}
            else
            if(gettype($args[1]) == "string"){self::$ext_filter[] = strtolower($args[1]);}
        }

        // Grab path(s)
        self::verifyPaths($args[0]);
        return self::$files;
    }

    static private function verifyPaths($paths){
        $path_errors = array();
        if(gettype($paths) == "string"){$paths = array($paths);}

        foreach($paths as $path){
            if(is_dir($path)){
                self::$directories[] = $path;
                $dirContents = self::find_contents($path);
            } else {
                $path_errors[] = $path;
            }
        }

        if($path_errors){echo "The following directories do not exists<br />";die(var_dump($path_errors));}
    }

    // This is how we scan directories
    static private function find_contents($dir){
        $result = array();
        $root = scandir($dir);
        foreach($root as $value){
            if($value === '.' || $value === '..') {continue;}
            if(is_file($dir.DIRECTORY_SEPARATOR.$value)){
                if(!self::$ext_filter || in_array(strtolower(pathinfo($dir.DIRECTORY_SEPARATOR.$value, PATHINFO_EXTENSION)), self::$ext_filter)){
                    self::$files[] = $result[] = $dir.DIRECTORY_SEPARATOR.$value;
                }
                continue;
            }
            if(self::$recursive){
                foreach(self::find_contents($dir.DIRECTORY_SEPARATOR.$value) as $value) {
                    self::$files[] = $result[] = $value;
                }
            }
        }
        // Return required for recursive search
        return $result;
    }
}




// Usage:
// scanDir::scan(path(s):string|array, [file_extensions:string|array], [subfolders?:true|false]);


//Scan a single directory for all files, no sub-directories
#$files = scanDir::scan('D:\Websites\temp');

//Scan multiple directories for all files, no sub-dirs
// $dirs = array(
//     'D:\folder',
//     'D:\folder2',
//     'C:\Other' );


#$files = scanDir::scan($dirs);

// Scan multiple directories for files with provided file extension,
// no sub-dirs


#$files = scanDir::scan($dirs, "jpg");
//or with an array of extensions
// $file_ext = array(
//     "jpg",
//     "bmp",
//     "png"
// );
#$files = scanDir::scan($dirs, $file_ext);

// Scan multiple directories for files with any extension,
// include files in recursive sub-folders
#$files = scanDir::scan($dirs, false, true);

// Multiple dirs, with specified extensions, include sub-dir files
#$files = scanDir::scan($dirs, $file_ext, true);



// $file_ext = [ "jpg","bmp","png"];
// $dirs[] = 'img';
// $filelist =  scanDir::scan($dirs, $file_ext, true);
// print_r($filelist);








