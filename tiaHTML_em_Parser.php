<?php

/**
 * Método especial - Ensino Médio
 */

function tiaParserEnsinoMedioNotaWithContent($content) {

	$doc = new DOMDocument();
	@$doc->loadHTML($content);
	$nodes = $doc->getElementById('tabela');
	$tbody = $doc->getElementsByTagName('tbody');
	$arrayMaterias = array();
	$arrayNotas = array();
	$arrayFormulas = array();
	$arrayTotal = array();
	$isFormulaTurn = FALSE;

	$rows = $doc->getElementsByTagName("tr");

	for ($i = 4, $posMat = 0, $posNotaXMat = 0, $posArrayTotalWFormulas = 0, $posArrayTotal = 0; $i < $rows->length; $i++) {

	    $cols = $rows->item($i)->getElementsbyTagName("td");
	
		if (!$isFormulaTurn) {
		    for ($j = 0, $posNota = 0, $posFormula = 0; $j < $cols->length; $j++) {
		    	
				if ($j == 1) {
					$arrayMaterias[$posMat++] = correctNomeMateria($cols->item($j)->textContent);
				}
				else if ($j > 1 && removeTrashFromString($cols->item($j)->textContent) !== "verlegenda") {
					
					$arrayNotas[$posNota++] = removeTrashFromString($cols->item($j)->textContent);
				}

				if ($cols->item($j)->textContent == "FÓRMULA") {
					$isFormulaTurn = true;
					break;
				}

				if ($j == $cols->length-2) {
					$arrayTotal[$posArrayTotal] = ['nome' => $arrayMaterias[$posNotaXMat],
												    'notas' => $arrayNotas,
													'formulas' => null,
													'id' => $posArrayTotal];
				
					$posArrayTotal++;
					$posNotaXMat++;
					$arrayNotas = array();
				}
			}
		}
		else {
			for ($j = 0, $posNota = 0, $posFormula = 0; $j < $cols->length; $j++) {

				if ($j > 1) {
					$arrayFormulas[$posFormula++] = removeTrashFromStringComplete($cols->item($j)->textContent);
				}

				if (strpos($cols->item($j)->textContent, "Fórmula") !== false) {
					continue;
				}

				if ($j == $cols->length-1) {
					if ($j > 1) {
						$arrayTotal[$posArrayTotalWFormulas++]['formulas'] = $arrayFormulas;
					}
				}	
		    }
		}
	}
	if (count($arrayTotal) == 0) {
		$arrayTotal = ['isInvalid' => true,
						'shouldBlockNotas' => false];
	}

	return json_encode($arrayTotal, JSON_UNESCAPED_UNICODE);
}

?>