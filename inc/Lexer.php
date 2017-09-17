<?php

class Lexer
{
	public $tokenVal = array();

	public function addToVals($array)
	{
		$this->tokenVal = array_merge($this->tokenVal, $array);
	}

	public function Tokenize($page, $pagename)
	{
		$Tokens = array();

		if ( LEXER_REGEX == false )
		{
			$currentToken = Token::T_NONE;
			$currentTokenObj = null;

			$lines = explode("\n", $page);

			foreach ( $lines as $key => $i )
				$lines[$key] = $i."\n";

			$currentTokVal = '';

			$i = 0;

			$pages = array();
			$index = 0;			

			$escapeNext = false;

			foreach ( $lines as $key => $page2 )
			{
				$i2 = 0; 

				$trigger[Token::T_SONICVAR] = false;
				$trigger[Token::T_CONTPROP] = false;
				$trigger[Token::T_RUNFUNC] = false;

				$triggering = true;

				$pages[$index] = '';

				while ( $i2 <= strlen($page2) - 1 )
				{
					if ( $page2[$i2] !== '\\' || $page2[$i2 + 1] == '\\')
						$pages[$index] .= $page2[$i2];

					$char = $page2[$i2];

					if ( $currentToken !== Token::T_NONE )
					{
						if ( $currentTokenObj->fetching === true )
						{
							if ( !isset($currentTokenObj->args[$currentTokenObj->argIndex]) )
								$currentTokenObj->args[$currentTokenObj->argIndex] = '';

							$currentTokenObj->args[$currentTokenObj->argIndex] .= $char;
						}
						elseif ( !$currentTokenObj->fetching && $currentTokenObj->hasArgs )
						{
							if ( !$currentTokenObj->coolDown )
							{
								if ( !Token::validateChar('runFuncArg', $char) && $char !== ')' )
									throw new SonicException('Unexpected character \''.$char.'\', expecting '.Token::getCloseChar('runFuncArg'), 620, null, $key + 1, VIEWS_DIR.$pagename);
							}
							else
							{
								if ( !Token::validateChar('runFuncArgCool', $char) && $char !== ')' )
									throw new SonicException('Unexpected character \''.$char.'\', expecting \',\', or \' \'', 620, null, $key + 1, VIEWS_DIR.$pagename);								
							}
						}
						else
						{
							if ( !Token::validateChar($currentToken, $char) && $char !== Token::getCloseChar($currentToken) )
								throw new SonicException('Unexpected character \''.$char.'\', expecting '.Token::getCloseChar($currentToken), 620, null, $key + 1, VIEWS_DIR.$pagename);
							else
								$currentTokVal .= $char;
						}
					}

					if ( $currentToken !== Token::T_NONE )
						$currentTokenObj->coolDown = false;

					if ( $triggering == true && $currentToken == Token::T_NONE )
					{
						foreach ( $trigger as $key10 => $i5 )
						{
							if ( $i5 == true )
							{
								if ( $char !== Token::getStartChar($key10) )
								{
									$triggering = false;
									$trigger[$key10] = false;
								}
							}
						}
					}

					switch ( $char )
					{
						case '\\':
							$escapeNext = true;
						break;
						case '%':
							if ( !$escapeNext )
							{
								if ( $trigger[Token::T_SONICVAR] == false )
								{
									$triggering = true;
									$trigger[Token::T_SONICVAR] = true;
								}
								else
								{
									$triggering = false;
									$trigger[Token::T_SONICVAR] = false;

									if ( $currentToken == Token::T_SONICVAR )
									{
										$currentTokenObj->endToken($i + 1, $currentTokVal);
										$Tokens[] = $currentTokenObj;

										$currentToken = Token::T_NONE;
										$currentTokVal = '';							
									}
									else
									{
										$currentToken = Token::T_SONICVAR;
										$currentTokenObj = new Token(Token::T_SONICVAR, $i, $key + 1);
									}
								}
							}
							else
								$escapeNext = false;
						break;
						case '[':
							if ( !$escapeNext )
							{
								if ( $trigger[Token::T_CONTPROP] == false )
								{
									$triggering = true;
									$trigger[Token::T_CONTPROP] = true;
								}
								else
								{
									$triggering = false;
									$trigger[Token::T_SONICVAR] = false;

									$currentToken = Token::T_CONTPROP;
									$currentTokenObj = new Token(Token::T_CONTPROP, $i, $key + 1);
								}
							}
							else
								$escapeNext = false;
						break;
						case ']':
							if ( !$escapeNext )
							{
								if ( $trigger[Token::T_SONICVAR] == false )
								{
									$triggering = true;
									$trigger[Token::T_SONICVAR] = true;
								}
								else
								{
									if ( $currentToken == Token::T_CONTPROP )
									{
										$currentTokenObj->endToken($i + 1, $currentTokVal);
										$Tokens[] = $currentTokenObj;

										$currentToken = Token::T_NONE;
										$currentTokVal = '';
									}
								}
							}
							else
								$escapeNext = false;
						break;
						case '{':
							if ( !$escapeNext )
							{
								if ( $trigger[Token::T_RUNFUNC] == false )
								{
									$triggering = true;
									$trigger[Token::T_RUNFUNC] = true;
								}
								else
								{		
									$triggering = false;
									$trigger[Token::T_SONICVAR] = false;

									$currentToken = Token::T_RUNFUNC;
									$currentTokenObj = new Token(Token::T_RUNFUNC, $i, $key + 1);
								}
							}
							else
								$escapeNext = false;
						break;
						case '}':
							if ( !$escapeNext )
							{
								if ( $currentToken == Token::T_RUNFUNC )
								{
									$currentTokenObj->endToken($i + 1, $currentTokVal);
									$Tokens[] = $currentTokenObj;

									$currentToken = Token::T_NONE;
									$currentTokVal = '';
								}
							}
							else
								$escapeNext = false;
						break;
						case '(':
							if ( !$escapeNext )
							{
								if ( $currentToken == Token::T_RUNFUNC )
								{
									$currentTokenObj->argOffs = true;
									$currentTokenObj->hasArgs = true;
								}
							}
							else
								$escapeNext = false;
						break;
						case ')':
							if ( !$escapeNext )
							{
								if ( $currentToken == Token::T_RUNFUNC && $currentTokenObj->hasArgs )
									$currentTokenObj->hasArgs = false;
							}
							else
								$escapeNext = false;
						break;
						case ',':
							if ( !$escapeNext )
							{
								if ( $currentToken == Token::T_RUNFUNC && $currentTokenObj->hasArgs && !$currentTokenObj->fetching )
									$currentTokenObj->argIndex += 1;
							}
							else
								$escapeNext = false;
						break;
						case '"':
							if ( !$escapeNext )
							{
								if ( $currentToken == Token::T_RUNFUNC && $currentTokenObj->hasArgs && !$currentTokenObj->fetching )
									$currentTokenObj->fetching = true;
								else
								{
									$currentTokenObj->coolDown = true;
									$currentTokenObj->fetching = false;
								}
							}
							else
								$escapeNext = false;
						break;
					}

					$i++;
					$i2++;
				}

				$index++;
			}
		}
		else
			preg_match_all('/{([A-z0-9-_]*)}/U', $page, $Tokens);

		return (object)array("page" => implode('', $pages), "Tokens" => $Tokens, "Name" => $pagename);
	}

	public function Interpret($page, $pg)
	{
		$Lex = $this->Tokenize($page, $pg);
		$additive = 0;

		if ( LEXER_REGEX )
		{
			foreach ( $Lex->Tokens[0] as $key => $i )
			{
				if ( isset($this->tokenVal[$Lex->Tokens[1][$key]]) )
					$Lex->page = str_replace($i, $this->tokenVal[$Lex->Tokens[1][$key]], $Lex->page);
			}

			return $Lex->page;
		}
		else
		{			
			foreach ( $Lex->Tokens as $i )
			{
				switch ( $i->type )
				{
					case Token::T_SONICVAR:
						if ( isset($this->tokenVal[$i->val]) )
						{
							$Lex->page = substr_replace($Lex->page, $this->tokenVal[$i->val], ($i->start - 1) + $additive, ($i->length + 1));
							$additive += strlen($this->tokenVal[$i->val]) - ($i->length + 1);
						}
					break;
					case Token::T_CONTPROP:

						$exp = explode('->', $i->val);

						if ( count($exp) === 2 )
						{
							$get = ContProp::cpv($exp[0], $exp[1]);

							if ( $get !== false )
							{
								$Lex->page = substr_replace($Lex->page, $get, ($i->start - 1) + $additive, ($i->length + 1));
								$additive += strlen($get) - ($i->length + 1);	
							}
						}
					break;
					case Token::T_RUNFUNC:

						$func = $i->getFuncInfo();

						if ( method_exists(new LexCalls, $func['name']) )
						{
							$get = call_user_func_array(array(new LexCalls, $func['name']), $func['args']);

							$Lex->page = substr_replace($Lex->page, $get, ($i->start - 1) + $additive, ($i->length + 2));
							$additive += strlen($get) - ($i->length + 2);								
						}
						else
							throw new SonicException('LexCalls method '.$func['name'].' does not exist', 629);
					break;
				}
			}
			
			return $Lex->page;
		}
	}

	public function Compile($pageArray)
	{
		if ( !is_array($pageArray) )
			throw new SonicException('First argument for Lexer::Compile must be an array', 622);

		$html = '';

		foreach ( $pageArray as $i )
		{
			ob_start();

			if ( !file_exists(VIEWS_DIR.$i) )
				throw new SonicException('View component '.VIEWS_DIR.$i.' not found', 621);
		
			$this->pagename = $i;

			try
			{
				include VIEWS_DIR.$i;
			}
			catch ( PDOException $exception )
			{
				echo "<pre>";
					debug_print_backtrace();
				echo "</pre>";

				print(Text::Bind(GlobalConfig('sonicwulf')->get('styles/error'), array(
					"no"	=>	$exception->getCode(),
					"str"	=>	$exception->getMessage(),
					"fil"	=>	$exception->getFile(),
					"lin"	=>	$exception->getLine(),
					"labl"	=>	'Uncaught Exception!'
				)));

				exit;
			}

			$html .= $this->Interpret(ob_get_contents(), $i);

			ob_clean();
		}

		return $html;
	}
}

class Token
{
	public $start;
	public $type;
	public $length;
	public $val;
	public $line;

	public $args = array();
	public $argIndex = 0;
	public $hasArgs = false;
	public $fetching = false;
	public $argOffs = false;
	public $coolDown = false;

	const T_SONICVAR	= 4;
	const T_CONTPROP	= 3;
	const T_RUNFUNC		= 2;
	const T_NONE		= 0;

	static $chars;

	static function getCloseChar($token)
	{
		switch ( $token )
		{
			case self::T_SONICVAR:
				return '%';
			case self::T_CONTPROP:
				return ']';
			case self::T_RUNFUNC:
				return '}';
			case 'runFuncArg':
				return "')', or ','";
		}

		return false;
	}

	static function getStartChar($token)
	{
		switch ( $token )
		{
			case self::T_SONICVAR:
				return '%';
			case self::T_CONTPROP:
				return '[';
			case self::T_RUNFUNC:
				return '{';
		}

		return false;		
	}

	static function validateChar($token, $char)
	{
		switch ( $token )
		{
			case self::T_SONICVAR:
				return in_array($char, self::$chars['T_SONICVAR'], true);
			case self::T_CONTPROP:
				return in_array($char, self::$chars['T_CONTPROP'], true);
			case self::T_RUNFUNC:
				return in_array($char, self::$chars['T_RUNFUNC'], true);
			case 'runFuncArg':
				return in_array($char, self::$chars['T_RUNFUNCARG'], true);
			case 'runFuncArgCool':
				return in_array($char, self::$chars['T_RUNFUNCCOOL'], true);
		}

		return false;
	}

	public function getFuncInfo()
	{
		if ( $this->argOffs )
			$fn = substr($this->val, 0, (strlen($this->val) - 1));
		else
			$fn = $this->val;

		$data = array(
				"name"	=>	$fn,
				"args"	=>	array()
			);

		foreach ( $this->args as $i )
			$data['args'][] = stripslashes(substr($i, 0, (strlen($i) - 1)));

		return $data;
	}

	public function endToken($length, $val)
	{
		$this->length = $length - $this->start;
		$this->val = str_replace(array('%', '[', ']', '{', '}'), '', $val);
	}

	public function __construct($token, $start, $line)
	{
		$this->start = $start;
		$this->type = $token;
		$this->line = $line;

		self::$chars['T_SONICVAR'] = array_merge(range('A', 'Z'), range('a', 'z'), range(0, 9), array('_'));
		self::$chars['T_CONTPROP'] = array_merge(range('A', 'Z'), range('a', 'z'), range(0, 9), array('_', '-', '>'));
		self::$chars['T_RUNFUNC']  = array_merge(range('A', 'Z'), range('a', 'z'), range(0, 9), array('_', '(', ')'));
		self::$chars['T_RUNFUNCARG'] = array(',', ' ', '"');
		self::$chars['T_RUNFUNCCOOL'] = array(',', ' ');
	}
}