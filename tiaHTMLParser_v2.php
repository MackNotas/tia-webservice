<?php

include "tiaHTML_em_Parser.php";

// =====================================================================================
//  __  __            _    _   _       _                      _   _ _____ __  __ _     
// |  \/  | __ _  ___| | _| \ | | ___ | |_ __ _ ___          | | | |_   _|  \/  | |    
// | |\/| |/ _` |/ __| |/ /  \| |/ _ \| __/ _` / __|  _____  | |_| | | | | |\/| | |    
// | |  | | (_| | (__|   <| |\  | (_) | || (_| \__ \ |_____| |  _  | | | | |  | | |___ 
// |_|  |_|\__,_|\___|_|\_\_| \_|\___/ \__\__,_|___/         |_| |_| |_| |_|  |_|_____|
//
// =====================================================================================
                                                
//
//
// NOTAS
//
//

function tiaParserNotaWithContent($content) {
	//
	//	Iniciando leitura do HTML recebido
	//

	$doc = new DOMDocument();
	@$doc->loadHTML($content);
	$nodes = $doc->getElementById('tabela');
	$tbody = $doc->getElementsByTagName('tbody');
	$arrayMaterias = [];
	$arrayNotas = [];
	$arrayFormulas = [];
	$arrayTotal = [];
	$isFormulaTurn = FALSE;
	$hasDifferentNota = hasDifferentCurso($doc);
	$array_notas_new = [];

	if (isLoginCorrectWithDoc($doc) == false) {
		return json_encode(['login' => false]);
	}
	if (shouldBlockNotas($doc)) {
		return json_encode(['isInvalid' => true,
							'shouldBlockNotas' => true]);
	}
	if ($hasDifferentNota == DifferentCurso::cursoEM) {
		return tiaParserEnsinoMedioNotaWithContent($content);
	}

	// $arrayTotal[] = append_ad_notas('Download android: bit.ly/macknotas');

	$rows = $doc->getElementsByTagName("tr");
	// p_r($rows->item(0));exit;

	for ($i = 5, $posMat = 0, $posNotaXMat = 0, $posArrayTotalWFormulas = 0, $posArrayTotal = 0; $i < $rows->length; $i++) {

	    $cols = $rows->item($i)->getElementsbyTagName("td");

	    for ($j = 0, $posNota = 0, $posFormula = 0; $j < $cols->length; $j++) {

			if ($j == 1 && !$isFormulaTurn) {
				$arrayMaterias[$posMat++] = correctNomeMateria($cols->item($j)->textContent);
			}

			else if ($j > 1 && !$isFormulaTurn) {

				$nota_fixed = removeTrashFromString($cols->item($j)->textContent);
				$array_notas_new[] = $nota_fixed;

				//Pulando notas n1 e n2 para os apps antigos
				if ($j != 12 && $j != 13) {
					$arrayNotas[$posNota++] = $nota_fixed;
				}
			}

			if ($j > 1 && $isFormulaTurn) {
				$arrayFormulas[$posFormula++] = $cols->item($j)->textContent;
			}

			if (!strcmp($cols->item($j)->textContent, "FÓRMULA")) {
				$isFormulaTurn = TRUE;
			}

			if ($j == $cols->length-1) {
				if (!$isFormulaTurn) {
					$arrayTotal[] = ['nome' => $arrayMaterias[$posNotaXMat],
													'notas' => $arrayNotas,
													"notas_new" => $array_notas_new,
													'formulas' => null,
													'id' => $posArrayTotal];
				}
				else if ($isFormulaTurn && $j > 1) {
					$arrayTotal[$posArrayTotalWFormulas++]['formulas'] = $arrayFormulas;
				}
				$posArrayTotal++;
				$posNotaXMat++;
				$arrayNotas = [];
				$array_notas_new = [];
			}
	    }
	}
	if (count($arrayTotal) == 1) {
		$arrayTotal = ['isInvalid' => true,
						'shouldBlockNotas' => false];
	}
	else {
		// save_notas_on_parse($arrayTotal);
	}

	return json_encode($arrayTotal);
}

//
//
// VALIDAR LOGIN
//
//
function tiaParserValidarLoginWithContent($content) {
	$doc = new DOMDocument();
	@$doc->loadHTML($content);

	$isLoginCorrect = isLoginCorrectWithDoc($doc);

	if ($isLoginCorrect == true) {
		$hasDifferentCursoValue = hasDifferentCurso($doc);

		$arr = array('login' => true,
					 'nomeCompleto' => getNomeCompletoWithDoc($doc),
					 'curso' => getCursoWithDoc($doc),
					 'shouldBlockNotas' => shouldBlockNotas($doc),
					 'error_msg' => NULL,
					 'differentCurso' => $hasDifferentCursoValue);
	}
	else {
		$arr = array('login' => false,
					 'nomeCompleto' => "",
					 'curso' => "",
					 'error_msg' => NULL);
	}
	return json_encode($arr);
}

//
//
// FALTAS
//
//
function tiaParserFaltasWithContent($content) {

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
	$hasDifferentFalta = hasDifferentCurso($doc);

	if (isLoginCorrectWithDoc($doc) == false) {
		$arr = array('login' => false);
		return json_encode($arr);
		exit;
	}
	if ($hasDifferentFalta == DifferentCurso::cursoEM) {
		return json_encode(['isInvalid' => true]);
	}

	// if ($userTia == '31338526' || $userTia == '31348408' || $userTia == '31304801')  {
	// $arrayTotal[] = ['isInvalid' => true];
	// }

	$rows = $doc->getElementsByTagName("tr");

	for ($i = 5, $posMat = 0, $posNotaXMat = 0; $i < $rows->length; $i++) {
		$cols = $rows->item($i)->getElementsbyTagName("td");
	     for ($j = 0, $faltas = 0, $porcentagem = 0; $j < $cols->length; $j++) {
			if ($j == 1) {
				$arrayMaterias[$posMat++] = correctNomeMateria($cols->item($j)->textContent);
			}
			else if ($j > 1) {
				$faltas = $cols->item($j-2)->textContent;
				$porcentagem = $cols->item($j-1)->textContent;
				$ultimaData = $cols->item($j)->textContent;
				$permitido = $cols->item(4)->textContent;
			}
			if ($j == $cols->length-1) {
					$arrayTotal[] = ['nome' => $arrayMaterias[$posNotaXMat],
													'faltas' => $faltas,
													'porcentagem' => $porcentagem,
													'permitido' => $permitido,
													'ultimaData'	=>	$ultimaData];
				$posNotaXMat++;
				$arrayMaterias = array();
			}
	    }
	}
	// if (count($arrayTotal) == 0) {
	// 	$arrayTotal = ['isInvalid' => true];
	// }
	return json_encode($arrayTotal);
}

//
//
// CALENDARIO PROVAS
//
//
function tiaParserCalendarioWithContentAndMoodleCal($content, $moodleCal) {
	libxml_use_internal_errors(true);
	$doc = new DOMDocument();
	@$doc->loadHTML($content);
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
	

	if (isLoginCorrectWithDoc($doc) == false) {
		$arr = array('login' => false);
		return json_encode($arr);
		exit;
	}
	
	$rows = $doc->getElementsByTagName("tr");
	for ($i = 3, $posMat = 0, $posDataXMat = 0, $posArrayTotalWSubs = 0, $posArrayTotal = 0; $i < $rows->length; $i++) {
	    $cols = $rows->item($i)->getElementsbyTagName("td");

	    for ($j = 0, $posNota = 0, $posSub = 0; $j < $cols->length; $j++) {
			if ($j == 0 && !$isSubTurn) {
				$arrayMaterias[$posMat++] = correctNomeMateria($cols->item($j)->textContent);
			}
			else if ($j > 0 && !$isSubTurn) {
				$arraydiaSemana[$posNota] = getWeekDay($cols->item($j)->textContent);
				$arrayDia[$posNota] = getDay($cols->item($j)->textContent);
				$arrayMes[$posNota] = getMonthNumber($cols->item($j)->textContent);
				$arrayDatas[$posNota++] = trim($cols->item($j)->textContent);
			}
			if ($j > 0 && $isSubTurn) {
				if ($i+1 < $rows->length) { // Pois as Subs tem uma Coluna a mais
					$colsForSub = $rows->item($i+1)->getElementsbyTagName("td");
					$arrayMaterias[$posSub] = correctNomeMateria($colsForSub->item($j-1)->textContent);
					$arraySubdiaSemana[$posSub] = getWeekDay($colsForSub->item($j)->textContent);
					$arraySubDia[$posSub] = getDay($colsForSub->item($j)->textContent);
					$arraySubMes[$posSub] = getMonthNumber($colsForSub->item($j)->textContent);
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
		$arrayProvas[$i] = getProvasByMonthWithMonthNumber($arrayTotal, $i+1);
		$arrayProvasMoodle[$i] = getMoodleProvasByMonthWithMonthNumber($moodleCal, $i+1);
	}

	//
	//	Da merge no array_provas com array_provas_moodle com os objetos nas suas respectivas posições
	//
	$arrayMerged = array();
	for ($i = 0; $i < 12; $i++) { 

		if (!empty($arrayProvas[$i]) && !empty($arrayProvasMoodle[$i])) {
			$arrayMerged[$i] = array_merge($arrayProvas[$i], $arrayProvasMoodle[$i]);
			usort($arrayMerged[$i], 'sort_objects_by_date');
		}
		elseif (!empty($arrayProvasMoodle[$i])) {
			$arrayMerged[$i] = $arrayProvasMoodle[$i];
		}
		elseif (!empty($arrayProvas[$i])) {
			$arrayMerged[$i] = $arrayProvas[$i];
		}

		else {
			$arrayMerged[$i] = array();
		}
	}
	
	return json_encode($arrayMerged);
}

//
//
// HORARIO
//
//
function tiaParserHorarioWithContent($content){

	// libxml_use_internal_errors(true); // O mackenzie tem erros no XHTML deles nessa página.
	$doc = new DOMDocument();
	@$doc->loadHTML($content);
	$nodes = $doc->getElementById('tabela');
	$tbody = $doc->getElementsByTagName('tbody');
	$arrayMaterias = array();
	$arrayGrade = array();
	$arrayHorarios = array();
	$errorMessage = 'A grade horária ainda não está disponível!';

	if (isLoginCorrectWithDoc($doc) == false) {
		$arr = array('login' => false);
		return json_encode($arr);
		exit;
	}

	$rows = $doc->getElementsByTagName("tr");

	/**
	 * Retorna muita coisa além da tabela de horarios. Por isso, pegamos o total de rows e tiramos o número de colunas que queremos
	 * São elas: Horario, Segunda, Terca, quarta, quinta, sexta e sabado (7).
	 * Se for grade mista, o init deve comecar por baixo para pegar os dois horários. (7+7)
	 * Usei 12 para garantir algumas coisas que possam aparecer do nada em cima.
	 */
	
	$horario_init;

	if ($rows->length == 0) {
		$horario_init = 0;
	}
	else if ($rows->length > 20) {
		$horario_init = $rows->length - 22;
	}
	else if ($rows->length > 12) {
		$horario_init = $rows->length - 14;
	}
	else {
		$horario_init = $rows->length - 7;
	}

	for ($i = $horario_init, $posArrayTotal = 0, $posMateriaTotal = 0, $posCols = 1; $i < $rows->length; $i++) {

		//
		//	Obter as Horas
		//
		if ($i == $horario_init) {
			for ($j = $horario_init, $posHora = 0; $j < $rows->length; $j++) {
				$cols = $rows->item($j)->getElementsbyTagName("td");
				$stringHora = $cols->item(0)->textContent;

				if (strcmp($stringHora, "Hora") != 0) {
					$arrayHorarios[$posHora++] = $cols->item(0)->textContent;
				}
			}
		}

		//
		//	Obter as Materias
		//
		else if ($i > $horario_init && $i < 10) {
			for ($j = $horario_init, $posMateria = 0; $j < $rows->length; $j++) {
				$cols = $rows->item($j)->getElementsbyTagName("td");

				$nomeMateria = $cols->item($posCols)->textContent;
				$stringArray = str_split(utf8_decode(preg_replace('/\s+/', ' ', $nomeMateria)));

				if (strcmp($nomeMateria, "Segunda")	!= 0 &&
					strcmp($nomeMateria, "Terça")	!= 0 &&
					strcmp($nomeMateria, "Quarta")	!= 0 &&
					strcmp($nomeMateria, "Quinta")	!= 0 &&
					strcmp($nomeMateria, "Sexta")	!= 0 &&
					strcmp($nomeMateria, "Sábado")	!= 0) {
					$arrayMaterias[$posMateria++] = removeTrashFromMateriaStringArray($stringArray);
				}
			}
			$posCols++;
			$arrayGrade[$posMateriaTotal] = ['dia' => $posMateriaTotal,
											  'materias' => $arrayMaterias,
											  'horas' => $arrayHorarios,
											  'isInvalid' => false];

			$posMateriaTotal++;
		}
	}

	if (count($arrayGrade) == 0 &&
		count($arrayHorarios) == 0) {

		return json_encode(['grade' => [['dia' => 0,
										 'materias' => [$errorMessage],
										 'horas' => [''],
										 'isInvalid' => true]],
							'isInvalid' => true]);
	}	

	return json_encode(['grade' => $arrayGrade,
						'isInvalid' => false]);
}

//
//
// ATIVIDADE COMPLEMENTAR
//
//
function tiaParserAtivComplWithContent($content){
	libxml_use_internal_errors(true);
	$doc = new DOMDocument();
	@$doc->loadHTML($content);
	$tabelas = $doc->getElementsbyTagName('table');

	if (isLoginCorrectWithDoc($doc) == false) {
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

	for ($i = 1, $posArrayTotalA = 0; $i < $rows->length; $i++) {
		$cols = $rows->item($i)->getElementsbyTagName("td");

		 for ($j = 0; $j < $cols->length; $j++) {

			if (strpos($cols->item($j)->textContent, "Atividades de Ensino") !== false) {
				$isTotalTurn = TRUE;
			}

			//
			//	Primeira Tabela
			//
			if (!$isTotalTurn) {
				if ($j == 0 && !$isTotalTurn) {
					$tipoAtividade = removeTrashFromStringComplete($cols->item($j)->textContent);
				}
				else if ($j == 1 && !$isTotalTurn) {
					$data = removeTrashFromStringComplete($cols->item($j)->textContent);
				}
				else if ($j == 2 && !$isTotalTurn) {
					$strModalidade = removeTrashFromStringComplete($cols->item($j)->textContent);
				}
				else if ($j == 3 && !$isTotalTurn) {
					$strAssunto = removeTrashFromStringComplete($cols->item($j)->textContent);
				}
				else if ($j == 4 && !$isTotalTurn) {
					$strAnoSemestre = removeTrashFromStringComplete($cols->item($j)->textContent);
				}
				else if ($j == 5 && !$isTotalTurn) {
					$strHoras = removeTrashFromStringComplete($cols->item($j)->textContent);
				}
			}

			//
			//	Segunda Tabela
			//
			else if ($isTotalTurn) {
				if ($j == 0) {
					$strAtEnsino = removeTrashFromStringComplete($cols->item($j)->textContent);
				}
				else if ($j == 1) {
					$strAtPesquisa = removeTrashFromStringComplete($cols->item($j)->textContent);
				}
				else if ($j == 2) {
					$strAtExtensao = removeTrashFromStringComplete($cols->item($j)->textContent);
				}
				else if ($j == 3) {
					$strExcedentes = removeTrashFromStringComplete($cols->item($j)->textContent);
				}
				else if ($j == 4) {
					$strTotalHoras = removeTrashFromStringComplete($cols->item($j)->textContent);
				}				
			}

			if ($j == $cols->length-1) {
				if (!$isTotalTurn && ($tipoAtividade != "" && $strModalidade !== "" && $strAssunto !== "")) {
				$arrayTotalAtividades[$posArrayTotalA] = ['tipo' => $tipoAtividade,
															'data' => $data,
															'modalidade' => $strModalidade,
															'assunto' => $strAssunto,
															'anoSemestre' => $strAnoSemestre,
															'horas' => $strHoras,
															'id' => $posArrayTotalA];
				$posArrayTotalA++;			
				}
				else if ($isTotalTurn && $j > 1) {
					if (IS_FROM_IOS) {
						$TotalTotalHoras = ['atEnsino' => $strAtEnsino,
											'atPesquisa' => $strAtPesquisa,
											'atExtensao' => $strExcedentes,
											'excedentes' => $strAtExtensao,
											'total' => $strTotalHoras,
											'id' => -1];
					}
					else {
						$TotalTotalHoras = ['atEnsino' => $strAtEnsino,
											'atPesquisa' => $strAtPesquisa,
											'atExtensao' => $strAtExtensao,
											'excedentes' => $strExcedentes,
											'total' => $strTotalHoras,
											'id' => -1];
					}	
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
function tiaParserDesempenhoPessoal($content){
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

			$arraySemestre[] = removeTrashFromStringComplete($semestreaux);

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

function hasDifferentCurso($doc) {

	if (getCursoWithDoc($doc) == "Faculdade De Arquitetura E Urbanismo - Arquitetura E Urbanismo") {
		return DifferentCurso::cursoArq;
	}
	if (getCursoWithDoc($doc) == "Faculdade De Arquitetura E Urbanismo - Design") {
		return DifferentCurso::cursoDesign;
	}
	if (strpos(getCursoWithDoc($doc), "Pos-graduacao") !== false) {
		return DifferentCurso::cursoPOS;
	}
	if (getCursoWithDoc($doc) == "Ensino Medio") {
		return DifferentCurso::cursoEM;
	}

	return false;
}

/**
 * Valida se o login foi realizado com sucesso, pegando com base a tag do nome do usuário
 * @param  HTML Doc
 * @return boolean
 */
function isLoginCorrectWithDoc($doc) {

	$tiaENome = $doc->getElementsbyTagName('h2');
	return strlen(trim($tiaENome->item(0)->nodeValue)) > 10;
}

/**
 * Valida se o login foi realizado com sucesso, mas passando um $content ao invez de um doc.
 * @param  Content
 * @return boolean
 */
function isLoginCorrectWithContent($content) {
	$doc = new DOMDocument();
	@$doc->loadHTML($content);

	return isLoginCorrectWithDoc($doc);
}

/**
 * Obtem o nome do curso do usuário
 * @param  HTML Doc
 * @return Nome do curso
 */
function getCursoWithDoc($doc) {
	$nomeItem = $doc->getElementsbyTagName('h3');
	return removeTrashFromStringComplete($nomeItem->item(0)->nodeValue);
}
/**
 * Obtem o nome do usuário (sem o TIA)
 * @param  HTML Doc
 * @return Nome do usuário em upcase
 */
function getNomeCompletoWithDoc($doc) {
	$nomeItem = $doc->getElementsbyTagName('h2');

	$stringClean = preg_replace('/\s+/', ' ', str_replace('-', '', $nomeItem->item(0)->nodeValue));
	$stringSemTIA = substr($stringClean, 9);
	
	return $stringSemTIA;
}

//
// Formar o Array do Calendario de Provas Por Mes (Janeiro = 0)
//

function getProvasByMonthWithMonthNumber($provas, $monthNumber) {
	$regExp = '([^\/]+$)';

	$mesMatchedA = array();
	$mesMatchedB = array();

	$provasArrayByMonth = array();

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
// Formar o Array do Calendario de Provas Por Mes do Moodle (Janeiro = 0)
//

function getMoodleProvasByMonthWithMonthNumber($provas, $monthNumber) {
	$regExp = '([^\/]+$)';

	$mesMatchedA = array();
	$mesMatchedB = array();

	$provasArrayByMonth = array();

	for ($i = 0, $arrayPos = 0; $i < count($provas); $i++) { 
		$provaMonthNumber = $provas[$i]->mesNumero;
		if ($provaMonthNumber == $monthNumber) {
			$provasArrayByMonth[$arrayPos++] = $provas[$i];
		}
	}

	usort($provasArrayByMonth, 'sort_objects_by_date');
	return $provasArrayByMonth;
}

//
//	Metodos de Strings
//

/**
 * Faz as seguintes correções no nome da matéria:
 * 		- Remove espaços desnecessários
 * 		- Torna apenas as primeiras letras maiusculas
 * 		- Encode em UTF8 para evitar bugs com acentos
 * @param  Nome da matéria
 */
function correctNomeMateria($materiaNome) {
	return utf8_encode(ucwords(strtolower(trim($materiaNome))));
}

/**
 * Faz uma limpeza em qualquer string, realizando o seguinte:
 * 		- Remove \\r \\n \\t \ \\ e \\s de textos (Mackenzie não encoda certo)
 * 		- Encode em UTF8
 * @param  Qualquer string que precisar de limpeza
 */
function removeTrashFromString($string) {
	return utf8_encode(preg_replace("@[\\r|\\n|\\t|\\/|\\\"|\\s]+@", "", $string));
}

//
//	REMOVER:
//		-	Espacos desnecessarios (Seminario             MackMobile)
//		-	Lixos bizarros (\r\n\r)
//		-	Espacos Desnecessarios no fim (Seminario MackMobile          )
//		-	Converte tudo para minusculo, depois só primeiras letras para upcase
//

function removeTrashFromStringComplete($string) {
	$strSemEspacos = preg_replace('/\s+/', ' ', $string);
	return utf8_encode(ucwords(strtolower(trim(preg_replace("@[\\r|\\n|\\t]+@", "", $strSemEspacos)))));
}

//
//
// REMOVER ESPACOS DO NOME DAS MATERIAS
// EXE: REDES DE COMPUTADORES 5H (FCI) -> REDES DE COMPUTADORES
//
//
function removeTrashFromMateriaStringArray($stringArray) {

	if (strcmp($stringArray[0], "-") == 0) {
		return utf8_encode(implode($stringArray));
	}

	$originalNomeMateria = $stringArray;
	$positionBlank = count($stringArray);

	//
	//	TRANSFORMAR ISSO NUMA REGEX!
	//

	for ($i = 0; $i < count($stringArray); $i++) { 
		if ((strcmp($stringArray[$i], "(")) == 0 || ($stringArray[$i] === "P" && $stringArray[$i+1] === "r" && $stringArray[$i+3] === "d" &&
													 $stringArray[$i+4] === "i" && $stringArray[$i+5] === "o" && $stringArray[$i+6] === " " )) {
			$positionBlank = $i;
			break;
		}
	}

	$stringClean = utf8_encode(trim(implode(array_splice($stringArray, 0, $positionBlank))));
	$stringClean .= get_sala_predio_if_exist(utf8_encode(implode($originalNomeMateria)));

	return $stringClean;
}

function get_sala_predio_if_exist($nomeMateria) {

	$numeroPredio = get_predio_if_available($nomeMateria);
	$numeroSala = get_sala_if_available($nomeMateria);

	//
	//	O caso "Prédio 00 Sala 000" é inválido
	//

	if ($numeroPredio && $numeroSala && ($numeroPredio != "00" && $numeroSala != "000")) {
		return " (P$numeroPredio S$numeroSala)";
	}

	return "";
}

/**
 * Verifica se o usuário devera ter as notas bloqueadas por não serem suportadas ainda (Arquitetura exe) com base no curso
 * @param  DOC HTML
 * @return BOOL
 */
function shouldBlockNotas($doc) {
	return (getCursoWithDoc($doc) == "Faculdade De Arquitetura E Urbanismo - Arquitetura E Urbanismo" 	||
			getCursoWithDoc($doc) == "Faculdade De Arquitetura E Urbanismo - Design" 					||
			strpos(getCursoWithDoc($doc), "Pos-graduacao") !== false									||
			getCursoWithDoc($doc) == "Ensino Medio");
}

function append_ad_notas($adText, $json_response) {

	return ['nome' => $adText,
			'notas' => ["","","","","","","","","","","","","0.0","","0.0"],
			'formulas' => [""],
			'id' => 0];
}

function get_predio_if_available($nomeMateria) {

	$regexPredio = "/((?<=Prédio).[0-9]+)/i";

	preg_match($regexPredio, $nomeMateria, $matches);

	if ($matches && count($matches) > 0) {
		return trim($matches[0]);
	}

	return false;
}

function get_sala_if_available($nomeMateria) {

	$regexSala = "/((?<=Sala).[0-9]+)/i";

	preg_match($regexSala, $nomeMateria, $matches);

	if ($matches && count($matches) > 0) {
		return trim($matches[0]);
	}

	return false;
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