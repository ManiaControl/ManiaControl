<?php
class CustomZipArchive extends ZipArchive
	{
	  public function extractSubdirTo($destination, $subdir)
	  {
		$errors = array();
  
		// Prepare dirs
		$destination = str_replace(array("/", "\\"), DIRECTORY_SEPARATOR, $destination);
		$subdir = str_replace(array("/", "\\"), "/", $subdir);
  
		if (substr($destination, mb_strlen(DIRECTORY_SEPARATOR, "UTF-8") * -1) != DIRECTORY_SEPARATOR)
		  $destination .= DIRECTORY_SEPARATOR;
  
		if (substr($subdir, -1) != "/")
		  $subdir .= "/";
  
		// Extract files
		for ($i = 0; $i < $this->numFiles; $i++)
		{
		  $filename = $this->getNameIndex($i);
  
		  if (substr($filename, 0, mb_strlen($subdir, "UTF-8")) == $subdir)
		  {
			$relativePath = substr($filename, mb_strlen($subdir, "UTF-8"));
			$relativePath = str_replace(array("/", "\\"), DIRECTORY_SEPARATOR, $relativePath);
  
			if (mb_strlen($relativePath, "UTF-8") > 0)
			{
			  if (substr($filename, -1) == "/")  // Directory
			  {
				// New dir
				if (!is_dir($destination . $relativePath))
				  if (!@mkdir($destination . $relativePath, 0755, true))
					$errors[$i] = $filename;
			  }
			  else
			  {
				if (dirname($relativePath) != ".")
				{
				  if (!is_dir($destination . dirname($relativePath)))
				  {
					// New dir (for file)
					@mkdir($destination . dirname($relativePath), 0755, true);
				  }
				}
  
				// New file
				if (@file_put_contents($destination . $relativePath, $this->getFromIndex($i)) === false)
				  $errors[$i] = $filename;
			  }
			}
		  }
		}
  
		return $errors;
	  }
	}
?>