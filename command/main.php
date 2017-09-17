<?php

function p($text, $module = '', $ln = true)
{
	if ( $ln )
		$suffix = PHP_EOL;
	else
		$suffix = '';

	if ( strlen($module) < 1 )
		echo $text.$suffix;
	else
		echo '['.$text.'] '.$text.$suffix;
}

function ln()
{
	echo PHP_EOL;
}

ln();
p('Welcome to the SonicWulf PHP command line utility');

while ( true )
{
	ln();
	p('Please choose an option: ');
	ln();

	p('1. Clear resource builds');
	p('2. Clear resource cache');
	p('3. Clear log directory');
	p('4. Compress project');
	p('5. Clear models');
	p('6. Modify configuration file');
	ln();

	p('Please enter an option (1-5): ', null, false);
	$input = stream_get_line(STDIN, 1024, PHP_EOL);

	$data = intval($input);

	ln();

	switch ( $data )
	{
		case 1:
			$data = glob('../resources/build/*');

			foreach ( $data as $i )
				unlink($i);

			p('Resource builds have been deleted');
		break;
		case 2:
			file_put_contents('../resources/dataCss', '');
			file_put_contents('../resources/dataImg', '{}');
			file_put_contents('../resources/dataJs', '');

			p('Resource cache has been cleared');
		break;
		case 3:
			$data = glob('../logs/*');

			foreach ( $data as $i )
				unlink($i);

			p('All logs have been deleted');
		break;
		case 4:
			$rootPath = realpath('..');

			$nameV = 'SonicWulf-Project-'.time().'.zip';

			$zip = new ZipArchive();
			$zip->open('../'.$nameV, ZipArchive::CREATE | ZipArchive::OVERWRITE);

			$files = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($rootPath),
			RecursiveIteratorIterator::LEAVES_ONLY
			);

			foreach ( $files as $name => $file )
			{
				if ( !$file->isDir() )
				{
					$filePath = $file->getRealPath();
					$relativePath = substr($filePath, strlen($rootPath) + 1);

					$zip->addFile($filePath, $relativePath);
				}
			}

			$zip->close();

			p('The project has been zipped on the root project directory as '.$nameV);
		break;
		case 5:
			$data = glob('../inc/dynamic/models/*');

			foreach ( $data as $i )
				unlink($i);

			p('Deleted all model classes');
		break;
		case 6:
			$data = glob('../conf.d/*.json');

			p('What configuration would you like to edit? (For multidimensional use JSON strings, existant values will be merged)');
			ln();

			foreach ( $data as $key => $i )
			{
				p(($key + 1).'. '.ucfirst(str_replace('../conf.d/', '', $i)));
			}

			ln();
			p('Please enter an option (1-'.count($data).'): ', null, false);
			$input = stream_get_line(STDIN, 1024, PHP_EOL);

			$intval = intval($input) - 1;

			if ( !isset($data[$intval]) )
			{
				p('Not a valid entry.');
				continue;
			}

			$file = json_decode(file_get_contents($data[$intval]), true);
			$filename = $data[$intval];

			if ( !$file )
			{
				p('Could not parse configuration file, please check JSON validity.');
				continue;
		 	}

		 	ln();
		 	p('Say SONICSEE to see the file, SONICSAVE to exit and save, or SONICDISCARD to exit on the KEY input.');
		 	ln();

		 	while ( true )
		 	{
				p('Enter the key of the element to be modified/added: ', '', false);
				$key = stream_get_line(STDIN, 1024, PHP_EOL);

				if ( $key === 'SONICSAVE' )
				{
					file_put_contents($filename, json_encode($file));
					continue 2;
				}
				elseif ( $key === 'SONICDISCARD' )
					continue 2;
				elseif ( $key === 'SONICSEE' )
				{
					ln();
					var_export($file);
					ln();
					ln();
					continue;
				}

				p('Enter the value of the element: ', '', false);
				$value = stream_get_line(STDIN, 1024, PHP_EOL);

				ln();

				if ( !isset($file[$key]) )
				{
					if ( json_decode($value) )
						$value = json_decode($value, true);

					$file[$key] = $value;
				}
				else
				{
					if ( is_array($file[$key]) && !json_decode($value, true) )
						p('This is a pre-existing element with an array value, please use a valid JSON string');
					elseif ( is_array($file[$key]) )
						$file[$key] = array_merge($file[$key], json_decode($value, true));
					elseif ( json_decode($value) )
						$file[$key] = json_decode($value);
					else
						$file[$key] = $value;
				}
		 	}
		break;
	}
}