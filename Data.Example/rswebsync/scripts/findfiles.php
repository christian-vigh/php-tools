#!/usr/bin/php
<?php
	$root		=  "__PATH__" ;
	$exclusions	=  [ __EXCLUSIONS__ ] ;
	$root_length	=  strlen ( $root ) ;
	$output		=  "__OUTPUT__" ;
	$rebuild	=  true ;			// For now, we always rebuild the tree file

	if  ( ! is_dir ( $root ) )
		exit ( 1 ) ;

	if  ( $root [ $root_length - 1 ]  ==  '/' )
		$root	=  substr ( $root, -- $root_length ) ;

	for  ( $i = 1 ; $i  <  count ( $argv ) ; $i ++ )
	   {
		switch  ( strtolower ( $argv [$i] ) )
		   {
			case	'-rebuild' :
				$rebuild	=  true ;
				break ;
		    }
	    }


	function  is_excluded ( $current_path )
	   {
		global	$root, $exclusions ;

		foreach  ( $exclusions  as  $exclusion )
		   {
			if  ( $exclusion [0]  ==  '/' ) 
			   {
				$exclusion_path		=  "$root$exclusion" ;

				if  ( ! strncmp ( $current_path, $exclusion_path, strlen ( $exclusion_path ) ) )
					return ( true ) ;
			    }
			else if  ( strpos ( $current_path, $exclusion )  !==  false ) 
				return ( true ) ;
		    }

		return ( false ) ;
	    }


	function  walkdir ( $path, $exclusions, &$array )
	   {
		global		$root_length ;


		$rs		=  @opendir ( $path ) ;

		if  ( ! $rs )
			exit ( 3 ) ;

		while  ( ( $file = readdir ( $rs ) ) )
		   {
			if  ( $file  ==  '.'  ||  $file  ==  '..' ) 
				continue ;

			$current_path	=  "$path/$file" ;

			if  ( is_excluded ( $current_path ) )
				continue ;

			$stat		=  stat ( $current_path ) ;
			$group_entry	=  posix_getgrgid ( $stat [ 'gid' ] ) ;
			$user_entry	=  posix_getpwuid ( $stat [ 'uid' ] ) ;
			$group		=  $group_entry [ 'name' ] ;
			$user		=  $user_entry [ 'name' ] ;
			$relative_path	=  substr ( $current_path, $root_length + 1 ) ;

			$array []	=  $relative_path . ';' . $stat [ 'mode' ] . ';' . $stat [ 'nlink' ] . ';' . $stat [ 'uid' ] . ';' . $user . ';' .
					   $stat [ 'gid' ] . ';' . $group . ';' . $stat [ 'size' ] . ';' . 
					   $stat [ 'atime' ] . ';' . $stat [ 'mtime' ] . ';' . $stat [ 'ctime' ] ;

			if  ( is_dir ( $current_path ) )
				walkdir ( $current_path, $exclusions, $array ) ;
		    }

		closedir ( $rs ) ;
	    }

	if  ( $rebuild  ||  ! file_exists ( $output ) )
	   {
		walkdir ( $root, $exclusions, $array ) ;
		//$array	=  array_merge ( [ "file;node;nlink;uid;user;gid;group;size;atime;mtime;ctime" ], $array ) ;

		file_put_contents ( $output, gzencode ( implode ( "\n", $array ) ) ) ;
		chmod ( $output, 0600 ) ;
	    }

	unlink ( $argv [0] ) ;
	
	exit ( 0 ) ;