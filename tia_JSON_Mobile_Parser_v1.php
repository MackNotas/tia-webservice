<?php

//	=====================================================================================================
//			  __  __            _    _   _       _                          _ ____   ___  _   _ 
//			 |  \/  | __ _  ___| | _| \ | | ___ | |_ __ _ ___              | / ___| / _ \| \ | |
//			 | |\/| |/ _` |/ __| |/ /  \| |/ _ \| __/ _` / __|  _____   _  | \___ \| | | |  \| |
//			 | |  | | (_| | (__|   <| |\  | (_) | || (_| \__ \ |_____| | |_| |___) | |_| | |\  |
//			 |_|  |_|\__,_|\___|_|\_\_| \_|\___/ \__\__,_|___/          \___/|____/ \___/|_| \_|
//			 
//	=====================================================================================================

function getMackNotasJSONForNotasMobile($JSON) {

	$finalJSON = [];
	$id = 0;

	foreach ($JSON as $materia) {
		$finalJSON[] = [
							"nome" => correctNomeMateria($materia["disciplina"]),
							"notas_new" => get_filtered_notas($materia["notas"]),
							"notas" => getOldNotasFromJSON($materia["notas"]),
							"formulas" => [$materia["formula"]],
							"id" => $id++,
						];
	}

	if (count($finalJSON) == 0) {
		$finalJSON[] = ['isInvalid' => true,
						'shouldBlockNotas' => false];
	}

	return json_encode($finalJSON);
}

function getMackNotasJSONForFaltasMobile($JSON) {

	$finalJSON = [];

	foreach ($JSON as $materia) {
		$finalJSON[] = [
							"nome" => correctNomeMateria($materia["disciplina"]),
							"faltas" => strval($materia["faltas"]),
							"total" => strval($materia["dadas"]),
							"permitido" => strval($materia["permit"]),
							"porcentagem" => strval(number_format($materia["percentual"], 2, ".", "")),
							"ultimaData" => $materia["atualizacao"]
						];
	}

	if (count($finalJSON) == 0) {
		$finalJSON[] = ['isInvalid' => true,
						'shouldBlockNotas' => false];
	}

	return json_encode($finalJSON);
}

function getOldNotasFromJSON($JSON) {
	$notas_only = [];

	foreach ($JSON as $key => $value) {
		if ($key == "NI 1") { break; }
		$notas_only[] = $value;
	}

	//
	//	O MackNotas segue a mesma ordem do TIA. O TIA mobile possui ordem diferente, a partir do n1/n2
	//

	$notas_only[] = $JSON["SUB"];
	$notas_only[] = $JSON["PARTIC"];
	$notas_only[] = $JSON["MI"];
	$notas_only[] = $JSON["PF"];
	$notas_only[] = $JSON["MF"] ?: "0.0";

	return $notas_only;
}

function get_filtered_notas($notas) {

    // Workaround até ser implementado no Android

	if (!IS_FROM_IOS) {
		return $notas;
	}

	return array_filter($notas, function($nota) {
		return $nota != "";
	});
}

?>
                                                                                   