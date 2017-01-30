<?php

class tiaHTMLParser {

	//
	//
	// NOTAS
	//
	//

	public function tiaParserNotaWithContent($content) {
		//
		//	Iniciando leitura do HTML recebido
		//
		// echo "Starting Parsing TIA HTML";

		$doc = new DOMDocument();
		@$doc->loadHTML($content);
		$nodes = $doc->getElementById('tabela');
		$tbody = $doc->getElementsByTagName('tbody');
		$arrayMaterias = array();
		$arrayNotas = array();
		$arrayFormulas = array();
		$arrayTotal = array();
		$isFormulaTurn = FALSE;

		if ($this->isLoginCorrectWithDoc($doc) == false) {
			$arr = array('login' => false);
			return json_encode($arr);
			exit;
		}

		$rows = $doc->getElementsByTagName("tr");

		for ($i = 5, $posMat = 0, $posNotaXMat = 0, $posArrayTotalWFormulas = 0, $posArrayTotal = 0; $i < $rows->length; $i++) {

		    $cols = $rows->item($i)->getElementsbyTagName("td");

		    for ($j = 0, $posNota = 0, $posFormula = 0; $j < $cols->length; $j++) {

				if ($j == 1 && !$isFormulaTurn) {
					$arrayMaterias[$posMat++] = $this->correctNomeMateria($cols->item($j)->textContent);
				}
				else if ($j > 1 && !$isFormulaTurn) {

					$arrayNotas[$posNota++] = $this->removeTrashFromString($cols->item($j)->textContent);
				}

				if ($j > 1 && $isFormulaTurn) {
					$arrayFormulas[$posFormula++] = $cols->item($j)->textContent;
				}

				if (!strcmp($cols->item($j)->textContent, "FÓRMULA")) {
					$isFormulaTurn = TRUE;
				}

				if ($j == $cols->length-1) {
					if (!$isFormulaTurn) {
						$arrayTotal[$posArrayTotal] = ['nome' => $arrayMaterias[$posNotaXMat],
														'notas' => $arrayNotas,
														'formulas' => null];
					}
					else if ($isFormulaTurn && $j > 1) {
						$arrayTotal[$posArrayTotalWFormulas++]['formulas'] = $arrayFormulas;
					}
					$posArrayTotal++;
					$posNotaXMat++;
					$arrayNotas = array();
				}
		    }
		}
		if (count($arrayTotal) == 0) {
			$arrayTotal[0] = ['nome' => 'Usuário não possui nenhuma matéria!',
								'notas' => ["","","","","","","","","","","","","","",""],
								'formulas' => null];
		}

		return json_encode($arrayTotal);
	}

	//
	//
	// VALIDAR LOGIN
	//
	//
	public function tiaParserValidarLoginWithContent($content){
		$doc = new DOMDocument();
		@$doc->loadHTML($content);

		$isLoginCorrect = $this->isLoginCorrectWithDoc($doc);

		if ($isLoginCorrect == true) {
			$arr = array('login' => true,
						 'nomeCompleto' => $this->getNomeCompletoWithDoc($doc),
						 'curso' => $this->getCursoWithDoc($doc),
						 'shouldBlockNotas' => $this->shouldBlockNotas($doc),
						 'error_msg' => NULL);
		}
		else {
			$arr = array('login' => false,
						 'nomeCompleto' => "",
						 'curso' => "",
						 'error_msg' => NULL);
		}
		return json_encode($arr);
		//return strlen($tiaENome->item(0)->nodeValue);
	}

	//
	//
	// FALTAS
	//
	//
	public function tiaParserFaltasWithContent($content){
		//echo "Starting Parsing TIA Faltas";
		$doc = new DOMDocument();
		@$doc->loadHTML($content);
		$nodes = $doc->getElementById('tabela');
		$tbody = $doc->getElementsByTagName('tbody');
		$arrayMaterias = array();
		$faltas;
		$porcentagem;
		$ultimaData;
		$permitido;
		$arrayTotal = array();

		if ($this->isLoginCorrectWithDoc($doc) == false) {
			$arr = array('login' => false);
			return json_encode($arr);
			exit;
		}

		$rows = $doc->getElementsByTagName("tr");
		for ($i = 5, $posMat = 0, $posNotaXMat = 0, $posArrayTotal = 0; $i < $rows->length; $i++) {
			$cols = $rows->item($i)->getElementsbyTagName("td");
		     for ($j = 0, $faltas = 0, $porcentagem = 0; $j < $cols->length; $j++) {
				if ($j == 1) {
					$arrayMaterias[$posMat++] = trim($cols->item($j)->textContent);
				}
				else if ($j > 1) {
					$faltas = $cols->item($j-2)->textContent;
					$porcentagem = $cols->item($j-1)->textContent;
					$ultimaData = $cols->item($j)->textContent;
					$permitido = $cols->item(4)->textContent;
				}
				if ($j == $cols->length-1) {
						$arrayTotal[$posArrayTotal] = ['nome' => $arrayMaterias[$posNotaXMat],
														'faltas' => $faltas,
														'porcentagem' => $porcentagem,
														'permitido' => $permitido,
														'ultimaData'	=>	$ultimaData];
					$posArrayTotal++;
					$posNotaXMat++;
					$arrayMaterias = array();
				}
		    }
		}
		if (count($arrayTotal) == 0) {
			$arrayTotal[0] = ['nome' => 'Usuário não possui nenhuma matéria!',
								'faltas' => 0,
								'porcentagem' => '00',
								'permitido' => 0,
								'ultimaData' =>	"00/00/0000"];
		}
		return json_encode($arrayTotal);
	}

	//
	//
	// CALENDARIO PROVAS
	//
	//
	public function tiaParserCalendarioWithContentAndMoodleCal($content, $moodleCal) {
		libxml_use_internal_errors(true);
		$doc = new DOMDocument();
		$doc->loadHTML($content);
		$tabelas = $doc->getElementsByTagName('table');
		$arrayTotal = array();
		$arrayMaterias = array();
		$arrayDatas = array();
		$arraySubs = array();
		$arraydiaSemana = array();
		$arrayDia = array();
		$arrayMes = array();
		$isSubTurn = FALSE;

		// Sub Array
		$arraySubdiaSemana = array();
		$arraySubDia = array();
		$arraySubMes = array();

		//Prova Object
		$arrayProvas = array();
		$arrayTipoProva = ['Prova A',
							'Prova B',
							'Prova C',
							'Prova D',
							'Prova E',
							'Prova F',
							'Prova G',
							'Prova H',
							'Prova I',
							'Prova J',
							'Prova Final',
							'Vista da Prova Final'];
		

		if ($this->isLoginCorrectWithDoc($doc) == false) {
			$arr = array('login' => false);
			return json_encode($arr);
			exit;
		}
		
		$rows = $doc->getElementsByTagName("tr");
		for ($i = 3, $posMat = 0, $posDataXMat = 0, $posArrayTotalWSubs = 0, $posArrayTotal = 0; $i < $rows->length; $i++) {
		    $cols = $rows->item($i)->getElementsbyTagName("td");

		    for ($j = 0, $posNota = 0, $posSub = 0; $j < $cols->length; $j++) {
		    	// $provaObject = new Prova();
				if ($j == 0 && !$isSubTurn) {
					$arrayMaterias[$posMat++] = $this->correctNomeMateria($cols->item($j)->textContent);
				}
				else if ($j > 0 && !$isSubTurn) {
					$arraydiaSemana[$posNota] = $this->getWeekDay($cols->item($j)->textContent);
					$arrayDia[$posNota] = $this->getDay($cols->item($j)->textContent);
					$arrayMes[$posNota] = $this->getMonthNumber($cols->item($j)->textContent);
					$arrayDatas[$posNota++] = trim($cols->item($j)->textContent);
				}
				if ($j > 0 && $isSubTurn) {
					if ($i+1 < $rows->length) { // Pois as Subs tem uma Coluna a mais
						$colsForSub = $rows->item($i+1)->getElementsbyTagName("td");
						$arrayMaterias[$posSub] = $this->correctNomeMateria($colsForSub->item($j-1)->textContent);
						$arraySubdiaSemana[$posSub] = $this->getWeekDay($colsForSub->item($j)->textContent);
						$arraySubDia[$posSub] = $this->getDay($colsForSub->item($j)->textContent);
						$arraySubMes[$posSub] = $this->getMonthNumber($colsForSub->item($j)->textContent);
						$arraySubs[$posSub++] = $colsForSub->item($j)->textContent;
					}
				}

				if (!strcmp($cols->item($j)->textContent, "PROVAS SUBSTITUTIVAS")) {
					$isSubTurn = TRUE;
				}

				if ($j == $cols->length-1) {
					if (!$isSubTurn) {

						//
						//Transforma as Provas em um objeto Prova (e depois a SUB)
						//
						for ($w = 0, $posProvasOb = 0; $w < count($arrayDatas); $w++) { 
							if (trim($arrayDatas[$w]) != "") {
								$provaObject = new Prova($arrayMaterias[$posDataXMat], 
															$arrayDatas[$w],
									 						$arrayTipoProva[$w],
									 						$arrayDia[$w],
									 						$arraydiaSemana[$w],
									 						$arrayMes[$w]);
								$arrayTotal[$posArrayTotal++] = $provaObject;
							}
						}
					}
					else if ($isSubTurn && $j > 1 && $i+1 < $rows->length) {
						$provaObject = NULL;
						if (trim($arraySubs[0]) != "") {
							$provaObject = new Prova($arrayMaterias[0], 
														$arraySubs[0],
							 							'Sub',
							 							$arraySubDia[0],
							 							$arraySubdiaSemana[0],
							 							$arraySubMes[0]);
							$arrayTotal[$posArrayTotal++] = $provaObject;
						}
					}
					$posDataXMat++;
					$arrayDatas = array();
				}
		    }
		}

		//
		//	Separar e ordenar os objetos PROVAS em seus respectivos meses (Janeiro = 0)
		//
		$arrayProvas = array();
		$arrayProvasMoodle = array();
		for ($i = 0; $i < 12; $i ++) {
			$arrayProvas[$i] = $this->getProvasByMonthWithMonthNumber($arrayTotal, $i+1);
			$arrayProvasMoodle[$i] = $this->getProvasByMonthWithMonthNumber($moodleCal, $i+1);
		}
		
		return json_encode(array_merge($arrayProvas, $arrayProvasMoodle));
	}

	//
	//
	// HORARIO
	//
	//
	public function tiaParserHorarioWithContent($content){
		// echo "Starting Parsing TIA Horario";
		libxml_use_internal_errors(true);
		$doc = new DOMDocument();
		$doc->loadHTML($content);
		$nodes = $doc->getElementById('tabela');
		$tbody = $doc->getElementsByTagName('tbody');
		$arrayHoras = array();
		$arrayMaterias = array();
		$arrayMateriasPerDay = array();
		$arrayHorarios = array();

		if ($this->isLoginCorrectWithDoc($doc) == false) {
			$arr = array('login' => false);
			return json_encode($arr);
			exit;
		}

		$rows = $doc->getElementsByTagName("tr");
		
		for ($i = 3, $posArrayTotal = 0, $posMateriaTotal = 0, $posCols = 1; $i < $rows->length; $i++) {

			//
			//	Obter as Horas
			//
			if ($i == 3) {
				for ($j = 3, $posHora = 0; $j < $rows->length; $j++) {
					$cols = $rows->item($j)->getElementsbyTagName("td");
					$stringHora = $cols->item(0)->textContent;

					if (strcmp($stringHora, "Hora") != 0) {
						$arrayHoras[$posHora++] = $cols->item(0)->textContent;
					}
				}
			}

			//
			//	Obter as Materias
			//
			else if ($i > 3 && $i < 10) {
				for ($j = 3, $posMateria = 0; $j < $rows->length; $j++) {
					$cols = $rows->item($j)->getElementsbyTagName("td");
					$nomeMateria = $cols->item($posCols)->textContent;
					$stringArray = str_split(preg_replace('/\s+/', ' ', $nomeMateria));

					if (strcmp($nomeMateria, "Segunda")	!= 0 &&
						strcmp($nomeMateria, "Terça")	!= 0 &&
						strcmp($nomeMateria, "Quarta")	!= 0 &&
						strcmp($nomeMateria, "Quinta")	!= 0 &&
						strcmp($nomeMateria, "Sexta")	!= 0 &&
						strcmp($nomeMateria, "Sábado")	!= 0) {
						$arrayMaterias[$posMateria++] = $this->removeTrashFromMateriaStringArray($stringArray);
					}
				}
				$posCols++;
				$arrayMateriasPerDay[$posMateriaTotal++] = $arrayMaterias;
			}
		}

		if (count($arrayMateriasPerDay) == 0 &&
			count($arrayHoras) == 0) {

			$arrayMateriasPerDay = [['Usuário não possui nenhuma matéria!']];
			$arrayHoras 		 = [""];
		}		

		return json_encode(['materias' => $arrayMateriasPerDay,
							'horas' => $arrayHoras]);
	}

	//
	//
	// ATIVIDADE COMPLEMENTAR
	//
	//
	public function tiaParserAtivComplWithContent($content){
		libxml_use_internal_errors(true);
		$doc = new DOMDocument();
		$doc->loadHTML($content);
		$tabelas = $doc->getElementsbyTagName('table');

		if ($this->isLoginCorrectWithDoc($doc) == false) {
			$arr = array('login' => false);
			return json_encode($arr);
			exit;
		}

		//
		//	Primeira Tabela
		//
		$tipoAtividade 	= NULL;
		$data 			= NULL;
		$strModalidade		= NULL;
		$strAssunto 		= NULL;
		$strAnoSemestre		= NULL;
		$strHoras 			= NULL;

		//
		//	Segunda Tabela
		//
		$strAtEnsino 	= NULL;
		$strAtPesquisa 	= NULL;
		$strAtExtensao 	= NULL;
		$strExcedentes 	= NULL;
		$strTotalHoras 	= NULL;

		//
		//	Comum
		//
		$arrayTotalAtividades 	= array();
		$TotalTotalHoras		= NULL;
		$isTotalTurn 			= FALSE;
		
		$rows = $doc->getElementsByTagName("tr");
		// print_r($rows->length);
		// print_r("\\n");

		for ($i = 1, $posArrayTotalA = 0; $i < $rows->length; $i++) {
			$cols = $rows->item($i)->getElementsbyTagName("td");
			// print_r($cols->length);
			 for ($j = 0; $j < $cols->length; $j++) {

				if (!strcmp($cols->item($j)->textContent, "Atividades de Ensino")) {
					$isTotalTurn = TRUE;
				}

				//
				//	Primeira Tabela
				//
				if (!$isTotalTurn) {
					if ($j == 0 && !$isTotalTurn) {
						$tipoAtividade = $this->removeTrashFromStringComplete($cols->item($j)->textContent);
					}
					else if ($j == 1 && !$isTotalTurn) {
						$data = $this->removeTrashFromStringComplete($cols->item($j)->textContent);
					}
					else if ($j == 2 && !$isTotalTurn) {
						$strModalidade = $this->removeTrashFromStringComplete($cols->item($j)->textContent);
					}
					else if ($j == 3 && !$isTotalTurn) {
						$strAssunto = $this->removeTrashFromStringComplete($cols->item($j)->textContent);
					}
					else if ($j == 4 && !$isTotalTurn) {
						$strAnoSemestre = $this->removeTrashFromStringComplete($cols->item($j)->textContent);
					}
					else if ($j == 5 && !$isTotalTurn) {
						$strHoras = $this->removeTrashFromStringComplete($cols->item($j)->textContent);
					}
				}

				//
				//	Segunda Tabela
				//
				else if ($isTotalTurn) {
					if ($j == 0) {
						$strAtEnsino = $this->removeTrashFromStringComplete($cols->item($j)->textContent);
					}
					else if ($j == 1) {
						$strAtPesquisa = $this->removeTrashFromStringComplete($cols->item($j)->textContent);
					}
					else if ($j == 2) {
						$strAtExtensao = $this->removeTrashFromStringComplete($cols->item($j)->textContent);
					}
					else if ($j == 3) {
						$strExcedentes = $this->removeTrashFromStringComplete($cols->item($j)->textContent);
					}
					else if ($j == 4) {
						$strTotalHoras = $this->removeTrashFromStringComplete($cols->item($j)->textContent);
					}				
				}

				if ($j == $cols->length-1) {
					if (!$isTotalTurn) {
					$arrayTotalAtividades[$posArrayTotalA++] = ['tipo' => $tipoAtividade,
																'data' => $data,
																'modalidade' => $strModalidade,
																'assunto' => $strAssunto,
																'anoSemestre' => $strAnoSemestre,
																'horas' => $strHoras];						
					}
					else if ($isTotalTurn && $j > 1) {
					$TotalTotalHoras = ['atEnsino' => $strAtEnsino,
										'atPesquisa' => $strAtPesquisa,
										'atExtensao' => $strAtExtensao,
										'excedentes' => $strExcedentes,
										'total' => $strTotalHoras];	
					}
				}
			}
		} 

		return json_encode(['atDeferidas' => $arrayTotalAtividades,
							'totalHoras' => $TotalTotalHoras]);
	}

	//
	//
	// Desempenho pessoal
	// Obtém dados do histórico do aluno para gerar dados
	//
	//
	public function tiaParserDesempenhoPessoal($content){
		$doc = new DOMDocument();
		@$doc->loadHTML($content);

		$arrayNotas = array();
		$arraySemestre = array();

		$table = $doc->getElementsByTagName("table");
		$rows = $table->item(1)->getElementsbyTagName("tr");
		$cols = $rows->item(2)->getElementsbyTagName("td");

		//variaveis aux para o for
		$semestreaux = null;
		$arrayNotasAux = array();

		for($i = 2; $i<$rows->length; $i++){
			//obtendo linha
			$cols = $rows->item($i)->getElementsbyTagName("td");

			//obtendo dados de colunas
			$semestre = $cols->item(0)->nodeValue;
			$nota = $cols->item(4)->nodeValue;

			if($semestreaux == null){
				$semestreaux = $semestre;
			}

			if($semestreaux!= $semestre || $i == $rows->length-1){
				if($i == $rows->length-1)
					$arrayNotasAux[] = $nota;

				$arraySemestre[] = $this->removeTrashFromStringComplete($semestreaux);

				$notaSemestreTotal = 0;
				foreach($arrayNotasAux as $notaArray){
					$notaSemestreTotal += $notaArray;
				}
				$notaSemestreTotal = $notaSemestreTotal/count($arrayNotasAux);
				$arrayNotas[] = number_format((float)$notaSemestreTotal, 2, '.', '');

				$semestreaux = $semestre;
				$arrayNotasAux = array();

			}

			$arrayNotasAux[] = $nota;

		}

		//calculando media geral
		$mediaGeral = 0;
		foreach ($arrayNotas as $nota) {
			$mediaGeral += $nota;
		}

		$mediaGeral = number_format((float)($mediaGeral/count($arrayNotas)), 2, '.', '');

		return json_encode(['semestre'   => $arraySemestre,
						  'semestrenotas'=> $arrayNotas,
						  'mediageral' => $mediaGeral]);
		
		
	}

	/*
		Métodos auxiliares
	*/
	public function isLoginCorrectWithDoc($doc) {
		$tiaENome = $doc->getElementsbyTagName('h2');

		if (!(strlen($tiaENome->item(0)->nodeValue) > 8)) {
			return false;
		}
		else {
			return true;
		}
	}

	public function getCursoWithDoc($doc) {
		$nomeItem = $doc->getElementsbyTagName('h3');
		return $this->removeTrashFromStringComplete($nomeItem->item(0)->nodeValue);
	}

	public function getNomeCompletoWithDoc($doc) {
		$nomeItem = $doc->getElementsbyTagName('h2');

		$stringClean = preg_replace('/\s+/', ' ', str_replace('-', '', $nomeItem->item(0)->nodeValue));
		$stringSemTIA = substr($stringClean, 9);
		
		return $stringSemTIA;
	}

	//
	// Formar o Array do Calendario de Provas Por Mes (Janeiro = 0)
	//

	public function getProvasByMonthWithMonthNumber($provas, $monthNumber) {
		$regExp = '([^\/]+$)';

		$mesMatchedA = array();
		$mesMatchedB = array();

		$provasArrayByMonth = array();

		// $fullDataA = $provas->data;
		// $fullDataB = $b->data;
		for ($i = 0, $arrayPos = 0; $i < count($provas); $i++) { 
			$fullData = $provas[$i]->data;
			if (trim($fullData) != "") {
				preg_match($regExp, $fullData, $mesMatchedA);
				if ($mesMatchedA[0] == $monthNumber) {
					$provasArrayByMonth[$arrayPos++] = $provas[$i];
				}
			}
		}

		usort($provasArrayByMonth, 'sort_objects_by_date');
		return $provasArrayByMonth;
	}

	//
	//	Metodos de Datas
	//

	public function getWeekDay($strDate) {
		setlocale(LC_TIME, 'pt_BR.UTF-8');
		$diaMesAno = $this->getFullDate($strDate);
		return trim($strDate) != "" ? ucwords(strftime("%a", strtotime($diaMesAno))) : "";	
	}
	public function getMonthString($strDate) {
		setlocale(LC_TIME, 'pt_BR.UTF-8');
		$diaMesAno = $this->getFullDate($strDate);
		return trim($strDate) != "" ? ucwords(strftime("%B", strtotime($diaMesAno))) : "";	
	}
	public function getMonthNumber($strDate) {
		setlocale(LC_TIME, 'pt_BR.UTF-8');
		$diaMesAno = $this->getFullDate($strDate);
		return trim($strDate) != "" ? strftime("%m", strtotime($diaMesAno)) : "";	
	}
	public function getDay($strDate) {
		setlocale(LC_TIME, 'pt_BR.UTF-8');
		$diaMesAno = $this->getFullDate($strDate);
		return trim($strDate) != "" ? trim(strftime("%e", strtotime($diaMesAno))) : "";	
	}
	public function getFullDate($strDate) {
		setlocale(LC_TIME, 'pt_BR.UTF-8');
		return str_replace("/", "-", trim($strDate)) . "-" . date("Y");
	}

	//
	//	Metodos de Strings
	//

	public function correctNomeMateria($materiaNome) {
		return utf8_encode(ucwords(strtolower(trim($materiaNome))));
	}

	public function removeTrashFromString($string) {
		// return $string;
		return utf8_encode(preg_replace("@[\\r|\\n|\\t|\\/|\\\"|\\s]+@", "", $string));
	}
	//
	//	REMOVER:
	//		-	Espacos desnecessarios (Seminario             MackMobile)
	//		-	Lixos bizarros (\r\n\r)
	//		-	Espacos Desnecessarios no fim (Seminario MackMobile          )
	//		-	Converte tudo para minusculo, depois só primeiras letras para upcase
	//

	public function removeTrashFromStringComplete($string) {
		$strSemEspacos = preg_replace('/\s+/', ' ', $string);
		return utf8_encode(ucwords(strtolower(trim(preg_replace("@[\\r|\\n|\\t]+@", "", $strSemEspacos)))));
	}

	//
	//
	// REMOVER ESPACOS DO NOME DAS MATERIAS
	// EXE: REDES DE COMPUTADORES 5H (FCI) -> REDES DE COMPUTADORES
	//
	//
	public function removeTrashFromMateriaStringArray($stringArray) {

		if (strcmp($stringArray[0], "-") == 0) {
			return utf8_encode(implode($stringArray));
		}

		$positionBlank = 0;
		for ($i = 0; $i < count($stringArray); $i++) { 
			if ((strcmp($stringArray[$i], "(")) == 0) {
				$positionBlank = $i;
				break;
			}
		}

		$stringClean = array_splice($stringArray, 0, $positionBlank);
		return utf8_encode(trim(implode($stringClean)));
		// return $stringArray;
	}

	public function shouldBlockNotas($doc) {
		return ($this->getCursoWithDoc($doc) == "Faculdade De Arquitetura E Urbanismo - Arquitetura E Urbanismo");
	}

}

//
//	Ordenar os objetos Provas por dia
//

function sort_objects_by_date($a, $b) {
	if($a->dia == $b->dia){
		return 0;
	}
	return ($a->dia < $b->dia) ? -1 : 1;
}

class Prova {

	public $materia;
	public $data;
	public $tipo;
	public $dia;
	public $diaSemana;
	public $mesNumero;

	public function __construct($materia, $data, $tipo, $dia, $diaSemana, $mesNumero) {
		$this->materia = $materia;
		$this->data = $data;
		$this->tipo = $tipo;
		$this->dia = $dia;
		$this->diaSemana = $diaSemana;
		$this->mesNumero = $mesNumero;
	}
}

?>