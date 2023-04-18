<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cursos;
use App\Models\Clientes;
use Illuminate\Support\Facades\Validator;
use function GuzzleHttp\Promise\all;
use Illuminate\Support\Facades\DB;

class CursosController extends Controller
{
    // Mostrar todos los registros
    public function index(Request $request)
    {
        $token = $request->header('Authorization');
        $clientes = Clientes::all();
        $json = array();
        foreach ($clientes as $key => $cliente) {
            if ("Basic " . base64_encode($cliente["id_cliente"] . ":" . $cliente["llave_secreta"]) == $token) {
                if (isset($_GET["page"])) {
                    $cursos = DB::table('cursos')
                        ->join('cliente', 'cursos.id_creador', '=', 'cliente.id')
                        ->select(
                            'cursos.id',
                            'cursos.titulo',
                            'cursos.descripcion',
                            'cursos.instructor',
                            'cursos.imagen',
                            'cursos.id_creador',
                            'cliente.primer_nombre',
                            'cliente.primer_apellido'
                        )
                        ->paginate(10);
                } else {
                    $cursos = DB::table('cursos')
                        ->join('cliente', 'cursos.id_creador', '=', 'cliente.id')
                        ->select(
                            'cursos.id',
                            'cursos.titulo',
                            'cursos.descripcion',
                            'cursos.instructor',
                            'cursos.imagen',
                            'cursos.id_creador',
                            'cliente.primer_nombre',
                            'cliente.primer_apellido'
                        )
                        ->get();
                }
                if (!empty($cursos)) {
                    $json = array(
                        "status" => 200,
                        // "total_registros" => count($cursos),
                        "detalle" => $cursos
                    );
                    return json_encode($json, true);
                } else {
                    $json = array(
                        "status" => 404,
                        "total_registros" => 0,
                        "detalle" => "No hay ningún curso registrado"
                    );
                }
            } else {
                $json = array(
                    "status" => 404,
                    "detalle" => "No esta autorizado para recibir los registros"
                );
            }
        }
        // return json_encode($json, true);
    }


    // Crear un registro
    public function store(Request $request)
    {
        $token = $request->header('Authorization');
        $clientes = Clientes::all();
        $json = array();
        foreach ($clientes as $key => $cliente) {
            if ("Basic " . base64_encode($cliente["id_cliente"] . ":" . $cliente["llave_secreta"]) == $token) {
                $datos = array(
                    "titulo" => $request->input("titulo"),
                    "descripcion" => $request->input("descripcion"),
                    "instructor" => $request->input("instructor"),
                    "imagen" => $request->input("imagen"),
                    "precio" => $request->input("precio")
                );
                if (!empty($datos)) {
                    $validator = Validator::make($datos, [
                        'titulo' => 'required|string|max:255|unique:cursos',
                        'descripcion' => 'required|string|max:255|unique:cursos',
                        'instructor' => 'required|string|max:255',
                        'imagen' => 'required|string|max:255|unique:cursos',
                        'precio' => 'required|numeric',
                    ]);
                    if ($validator->fails()) {
                        $errors= $validator->errors();
                        $json = array(
                            "detalle" => $errors
                        );
                        return json_encode($json, true);
                    } else {

                        $curso = new Cursos();
                        $curso->titulo = $datos["titulo"];
                        $curso->descripcion = $datos["descripcion"];
                        $curso->instructor = $datos["instructor"];
                        $curso->imagen = $datos["imagen"];
                        $curso->precio = $datos["precio"];
                        $curso->id_creador = $cliente["id"];
                        $curso->save();
                        $json = array(
                            "status" => "200",
                            "detalle" => "Registro exitoso, su curso fue guardado",
                        );
                        return json_encode($json, true);
                    }
                } else {
                    $json = array(
                        "status" => "404",
                        "detalle" => "Los registros no pueden estar vacios",
                    );
                };
                // echo '<pre>';
                // print_r($datos);
                // echo '</pre>';
                // return;
            }
        }
        return json_encode($json, true);
    }
    // Tomar un registro
    public function show($id, Request $request)
    {
        $token = $request->header('Authorization');
        $clientes = Clientes::all();
        $json = array();
        foreach ($clientes as $key => $cliente) {
            if ("Basic " . base64_encode($cliente["id_cliente"] . ":" . $cliente["llave_secreta"]) == $token) {
                $curso = Cursos::where("id", $id)->get();
                if (!empty($curso)) {
                    $json = array(
                        "status" => 200,
                        "detalle" => $curso
                    );
                } else {
                    $json = array(
                        "status" => 404,
                        "detalle" => "No hay ningún curso registrado"
                    );
                }
            }
        }
        return json_encode($json, true);
    }
    // editar
    public function update($id, Request $request)
    {
        $token = $request->header('Authorization');
        $clientes = Clientes::all();
        $json = array();
        foreach ($clientes as $key => $cliente) {
            if ("Basic " . base64_encode($cliente["id_cliente"] . ":" . $cliente["llave_secreta"]) == $token) {
                $datos = array(
                    "titulo" => $request->input("titulo"),
                    "descripcion" => $request->input("descripcion"),
                    "instructor" => $request->input("instructor"),
                    "imagen" => $request->input("imagen"),
                    "precio" => $request->input("precio")
                );
                if (!empty($datos)) {
                    $validator = Validator::make($datos, [
                        'titulo' => 'required|string|max:255',
                        'descripcion' => 'required|string|max:255',
                        'instructor' => 'required|string|max:255',
                        'imagen' => 'required|string|max:255',
                        'precio' => 'required|numeric',
                    ]);
                    if ($validator->fails()) {
                        $errors= $validator->errors();
                        $json = array(
                            "detalle" => $errors
                        );
                        return json_encode($json, true);
                    } else {
                        $traer_curso = Cursos::where("id", $id)->get();
                        if ($cliente["id"] == $traer_curso[0]["id_creador"]) {
                            $datos = array(
                                "titulo" => $datos["titulo"],
                                "descripcion" => $datos["descripcion"],
                                "instructor" => $datos["instructor"],
                                "imagen" => $datos["imagen"],
                                "precio" => $datos["precio"]
                            );
                            $cursos = Cursos::where("id", $id)->update($datos);
                            $json = array(
                                "status" => "200",
                                "detalle" => "Registro exitoso, su curso fue actualizado",
                            );
                            return json_encode($json, true);
                        } else {
                            $json = array(
                                "status" => "404",
                                "detalle" => "No esta autorizado para modificar este curso",
                            );
                            return json_encode($json, true);
                        }
                    }
                } else {
                    $json = array(
                        "status" => "404",
                        "detalle" => "Los registros no pueden estar vacios",
                    );
                };
                // echo '<pre>';
                // print_r($datos);
                // echo '</pre>';
                // return;
            } else {
                $json = array(
                    "status" => "404",
                    "detalle" => "No esta autorizado para modificar este curso",
                );
            }
        }
        return json_encode($json, true);
    }
    public function destroy($id, Request $request)
    {
        $token = $request->header('Authorization');
        $clientes = Clientes::all();
        $json = array();
        foreach ($clientes as $key => $cliente) {
            if ("Basic " . base64_encode($cliente["id_cliente"] . ":" . $cliente["llave_secreta"]) == $token) {
                $curso = Cursos::where("id", $id)->get();
                if (!empty($curso)) {
                    if ($cliente["id"] == $curso[0]["id_creador"]) {
                        $curso = Cursos::where("id", $id)->delete();
                        $json = array(
                            "status" => "200",
                            "detalle" => "Registro exitoso, su curso fue eliminado",
                        );
                    } else {
                        $json = array(
                            "status" => 404,
                            "detalle" => "No tienes permisos para eliminar este curso"
                        );
                    }
                } else {
                    $json = array(
                        "status" => 404,
                        "detalle" => "No hay ningún curso registrado"
                    );
                }
            }
        }
        return json_encode($json, true);
    }
}
