<?php

namespace App\Console\Commands;

use DateTime;
use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class GenAsdecodeCrudApi extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'asdecode:crud-api {name} {champs*} {--validation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generer les fonctions crud API';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // enregistrer les params de la commande
        $arguments = $this->arguments();

        // formater le model en snake case
        $model_snake = Str::snake($arguments['name'], '_');

        //generer les resources 
        Artisan::call('make:resource '.$arguments['name'].'Resource');
        
        //creation des routes
        $file = fopen(base_path('routes/api.php'), "a");
        fwrite($file, PHP_EOL);
        fwrite($file, PHP_EOL);
        fwrite($file, "Route::get('/".$model_snake."s', [App\Http\Controllers\\".$arguments['name']."Controller::class, 'index']);" . PHP_EOL);
        fwrite($file, "Route::post('/".$model_snake."s', [App\Http\Controllers\\".$arguments['name']."Controller::class, 'store']);" . PHP_EOL);
        fwrite($file, "Route::get('/".$model_snake."s/{id}', [App\Http\Controllers\\".$arguments['name']."Controller::class, 'show']);" . PHP_EOL);
        fwrite($file, "Route::post('/".$model_snake."s/{id}/update', [App\Http\Controllers\\".$arguments['name']."Controller::class, 'update']);" . PHP_EOL);
        fwrite($file, "Route::post('/".$model_snake."s/{id}/destroy', [App\Http\Controllers\\".$arguments['name']."Controller::class, 'destroy']);" . PHP_EOL);
        fwrite($file, "Route::post('/".$model_snake."s/destroy-group', [App\Http\Controllers\\".$arguments['name']."Controller::class, 'destroy_group']); " . PHP_EOL);
        if ($this->option('validation') == true) {
            fwrite($file, "Route::post('/".$model_snake."s/{id}/validate', [App\Http\Controllers\\".$arguments['name']."Controller::class, 'validate_state']);" . PHP_EOL);
            fwrite($file, "Route::post('/".$model_snake."s/validate-group', [App\Http\Controllers\\".$arguments['name']."Controller::class, 'validate_state_group']); " . PHP_EOL); 
        }
        fclose($file);

        //creation du model
        $model = fopen(base_path('app/Models/'.$arguments['name'].'.php'), "w");
        fwrite($model, "<?php". PHP_EOL);
        fwrite($model, PHP_EOL);
        fwrite($model, "namespace App\Models;". PHP_EOL);
        fwrite($model, PHP_EOL);
        fwrite($model, "use Illuminate\Database\Eloquent\SoftDeletes;". PHP_EOL);
        fwrite($model, "use Illuminate\Database\Eloquent\Factories\HasFactory;". PHP_EOL);
        fwrite($model, "use Illuminate\Database\Eloquent\Model;". PHP_EOL);
        fwrite($model, PHP_EOL);
        fwrite($model, "class ".$arguments['name']." extends Model". PHP_EOL);
        fwrite($model, "{". PHP_EOL);
        fwrite($model, "    use HasFactory, SoftDeletes;". PHP_EOL);
        fwrite($model, "    protected $"."fillable = [". PHP_EOL);
        foreach ($arguments['champs'] as $column) {
            fwrite($model, "        '".$column."',". PHP_EOL);
        }
        if ($this->option('validation') == true){
            fwrite($model, "        'est_valide',". PHP_EOL);
            fwrite($model, "        'statut',". PHP_EOL);
        }
        
        fwrite($model, "    ];" . PHP_EOL);
        fwrite($model, "}". PHP_EOL);
        fclose($model);

        //utiliser la date actuelle pour le nom et l'ordre des migrations
        $currentdate = new DateTime();
        $date = $currentdate->format('Y_m_d_His');

        //creer la migration du model
        $migration = fopen(base_path('database/migrations/'.$date.'_create_'.$model_snake.'s_table.php'), "w");
        fwrite($migration, "<?php" . PHP_EOL);
        fwrite($migration, PHP_EOL);
        fwrite($migration, "use Illuminate\Database\Migrations\Migration;" . PHP_EOL);
        fwrite($migration, "use Illuminate\Database\Schema\Blueprint;" . PHP_EOL);
        fwrite($migration, "use Illuminate\Support\Facades\Schema;" . PHP_EOL);
        fwrite($migration, PHP_EOL);
        fwrite($migration, "return new class extends Migration" . PHP_EOL);
        fwrite($migration, "{" . PHP_EOL);
        fwrite($migration, PHP_EOL);
        fwrite($migration, "   public function up(): void". PHP_EOL);
        fwrite($migration, "   {" . PHP_EOL);
        fwrite($migration, "      Schema::create('".$model_snake."s', function (Blueprint $"."table) {". PHP_EOL);
        fwrite($migration, "         $"."table->id();". PHP_EOL);
        foreach ($arguments['champs'] as $column) {
            if ($column == 'description') {
                fwrite($migration, "         $"."table->text('".Str::snake($column, '_')."')->nullable();". PHP_EOL);
            }
            elseif (Str::substr($column, 0, 4) == 'date') {
                fwrite($migration, "         $"."table->date('".Str::snake($column, '_')."')->nullable();". PHP_EOL);
            }
            elseif (Str::substr($column, 0, 2) == 'nb') {
                fwrite($migration, "         $"."table->integer('".Str::snake($column, '_')."')->nullable();". PHP_EOL);
            }
            else fwrite($migration, "         $"."table->string('".Str::snake($column, '_')."')->nullable();". PHP_EOL);
        }
        if ($this->option('validation') == true){
           fwrite($migration, "         $"."table->boolean('est_valide')->default(false);". PHP_EOL);
            fwrite($migration, "         $"."table->string('statut')->default('Brouillon');". PHP_EOL); 
        }
        fwrite($migration, "         $"."table->timestamps();". PHP_EOL);
        fwrite($migration, "         $"."table->softDeletes();". PHP_EOL);
        fwrite($migration, "      });". PHP_EOL);
        fwrite($migration, "   }" . PHP_EOL);
        fwrite($migration, PHP_EOL);
        fwrite($migration, PHP_EOL);
        fwrite($migration, "   public function down(): void". PHP_EOL);
        fwrite($migration, "   {" . PHP_EOL);
        fwrite($migration, "      Schema::dropIfExists('".$model_snake."s');". PHP_EOL);
        fwrite($migration, "   }" . PHP_EOL);
        fwrite($migration, "};" . PHP_EOL);
        fclose($migration);

        //creation du controller
        $controller = fopen(base_path('app/Http/Controllers/'.$arguments['name'].'Controller.php'), "w");
        fwrite($controller, "<?php" . PHP_EOL);
        fwrite($controller, PHP_EOL);
        fwrite($controller, "namespace App\Http\Controllers;" . PHP_EOL);
        fwrite($controller, "use App\Models\\".$arguments['name'].";" . PHP_EOL);
        fwrite($controller, "use App\Http\Resources\\".$arguments['name']."Resource;" . PHP_EOL);
        fwrite($controller, "use Illuminate\Http\Request;" . PHP_EOL);
        fwrite($controller, "use Illuminate\Support\Facades\Validator;" . PHP_EOL);
        fwrite($controller, PHP_EOL);
        fwrite($controller, "class ".$arguments['name']."Controller extends Controller" . PHP_EOL);
        fwrite($controller, "{" . PHP_EOL);
        fwrite($controller, PHP_EOL);
        fwrite($controller, "    public function index() ". PHP_EOL);
        fwrite($controller, "    {" . PHP_EOL);
        fwrite($controller, "        $".$model_snake."s = ".$arguments['name']."::latest();". PHP_EOL);
        fwrite($controller, PHP_EOL);
        fwrite($controller, "        if (isset($"."_GET['req_count'])) return $"."this->filterByColumn($".$model_snake."s)->count();". PHP_EOL);
        fwrite($controller, PHP_EOL);
        fwrite($controller, "        return ".$arguments['name']."Resource::collection($"."this->AsdecodefilterBy($".$model_snake."s));". PHP_EOL);
        fwrite($controller, "    }" . PHP_EOL);
        fwrite($controller, PHP_EOL);
        fwrite($controller, "    public function store(Request $"."request) ". PHP_EOL);
        fwrite($controller, "    {" . PHP_EOL);
        fwrite($controller, "        $"."validator = Validator::make(". PHP_EOL);
        fwrite($controller, "           $"."request->all(),". PHP_EOL);
        fwrite($controller, "           [". PHP_EOL);
        foreach ($arguments['champs'] as $column) {
            fwrite($controller, "               //'".$column."' => 'required',". PHP_EOL);
        }
        fwrite($controller, "           ],". PHP_EOL);
        fwrite($controller, "           $"."messages = [". PHP_EOL);
        foreach ($arguments['champs'] as $column) {
            fwrite($controller, "               //'".$column.".required' => 'Le champ ".$column." ne peut etre vide',". PHP_EOL);
        }
        fwrite($controller, "           ]". PHP_EOL);
        fwrite($controller, "         );". PHP_EOL);
        fwrite($controller, PHP_EOL);
        fwrite($controller, "        $".$model_snake."s = ".$arguments['name']."::latest();". PHP_EOL);
        fwrite($controller, "        if ($".$model_snake."s". PHP_EOL);
        foreach ($arguments['champs'] as $column) {
            fwrite($controller, "        ->where('".$column."', $"."request->".$column.")". PHP_EOL);
        }
        fwrite($controller, "        ->first()) {". PHP_EOL);
        fwrite($controller, "           $"."messages = [ 'Cet enregistrement existe déjà' ];". PHP_EOL);
        fwrite($controller, "           return $"."this->sendApiErrors($"."messages);". PHP_EOL);
        fwrite($controller, "        }". PHP_EOL);
        fwrite($controller, PHP_EOL);
        fwrite($controller, "        if ($"."validator->fails()) return $"."this->sendApiErrors($"."validator->errors()->all());". PHP_EOL);
        fwrite($controller, PHP_EOL);
        fwrite($controller, "        $".$model_snake." = ".$arguments['name']."::create($"."request->all());" . PHP_EOL);
        fwrite($controller, "        return $"."this->sendApiResponse($".$model_snake.", '".Str::title($model_snake)." ajouté', 201);" . PHP_EOL);
        fwrite($controller, "    }" . PHP_EOL);
        fwrite($controller, PHP_EOL);
        fwrite($controller, "    public function show($"."id)". PHP_EOL);
        fwrite($controller, "    {" . PHP_EOL);
        fwrite($controller, "        return new ".$arguments['name']."Resource(".$arguments['name']."::find($"."id));". PHP_EOL);
        fwrite($controller, "    }" . PHP_EOL);
        fwrite($controller, PHP_EOL);
        fwrite($controller, "    public function update(Request $"."request, $"."id) ". PHP_EOL);
        fwrite($controller, "    {" . PHP_EOL);
        fwrite($controller, "        $"."validator = Validator::make(". PHP_EOL);
        fwrite($controller, "           $"."request->all(),". PHP_EOL);
        fwrite($controller, "           [". PHP_EOL);
        foreach ($arguments['champs'] as $column) {
            fwrite($controller, "               //'".$column."' => 'required',". PHP_EOL);
        }
        fwrite($controller, "           ],". PHP_EOL);
        fwrite($controller, "           $"."messages = [". PHP_EOL);
        foreach ($arguments['champs'] as $column) {
            fwrite($controller, "               //'".$column.".required' => 'Le champ ".$column." ne peut etre vide',". PHP_EOL);
        }
        fwrite($controller, "           ]". PHP_EOL);
        fwrite($controller, "         );". PHP_EOL);
        fwrite($controller, PHP_EOL);
        fwrite($controller, "        $".$model_snake."s = ".$arguments['name']."::latest();". PHP_EOL);
        fwrite($controller, "        if ($".$model_snake."s". PHP_EOL);
        foreach ($arguments['champs'] as $column) {
            fwrite($controller, "        ->where('".$column."', $"."request->".$column.")". PHP_EOL);
        }
        fwrite($controller, "        ->where('id','!=', $"."id)->first()) {". PHP_EOL);
        fwrite($controller, "           $"."messages = [ 'Cet enregistrement existe déjà' ];". PHP_EOL);
        fwrite($controller, "           return $"."this->sendApiErrors($"."messages);". PHP_EOL);
        fwrite($controller, "        }". PHP_EOL);
        fwrite($controller, PHP_EOL);
        fwrite($controller, "        if ($"."validator->fails()) return $"."this->sendApiErrors($"."validator->errors()->all());". PHP_EOL);
        fwrite($controller, PHP_EOL);
        fwrite($controller, "        $".$model_snake." = ".$arguments['name']."::find($"."id);" . PHP_EOL);
        fwrite($controller, "        $".$model_snake."->update($"."request->all());" . PHP_EOL);
        fwrite($controller, "        return $"."this->sendApiResponse($".$model_snake.", '".Str::title($model_snake)." modifié', 201);" . PHP_EOL);
        fwrite($controller, "    }" . PHP_EOL);
        fwrite($controller, PHP_EOL);
        fwrite($controller, "    public function destroy($"."id) ". PHP_EOL);
        fwrite($controller, "    {" . PHP_EOL);
        fwrite($controller, "        $".$model_snake." = ".$arguments['name']."::find($"."id);". PHP_EOL);
        fwrite($controller, "        $".$model_snake."->delete();". PHP_EOL);
        fwrite($controller, PHP_EOL);
        fwrite($controller, "        return $"."this->sendApiResponse($".$model_snake.", '".Str::title($model_snake)." supprimé');". PHP_EOL);
        fwrite($controller, "    }" . PHP_EOL);
        fwrite($controller, PHP_EOL);
        if ($this->option('validation') == true){
            fwrite($controller, "    public function validate_state($"."id)" . PHP_EOL);
            fwrite($controller, "   {" . PHP_EOL);
            fwrite($controller, "       $".$model_snake." = ".$arguments['name']."::find($"."id);" . PHP_EOL);
            fwrite($controller, "       $".$model_snake."->est_valide = true ;" . PHP_EOL);
            fwrite($controller, "       $".$model_snake."->statut = 'Confirmé' ;" . PHP_EOL);
            fwrite($controller, "       $".$model_snake."->update();" . PHP_EOL);
            fwrite($controller, "       return $"."this->sendApiResponse($".$model_snake.", '".Str::title($model_snake)." validé');". PHP_EOL);
            fwrite($controller, "   }" . PHP_EOL);
            fwrite($controller, PHP_EOL);
            fwrite($controller, "    public function validate_state_group(Request $"."request){" . PHP_EOL);
            fwrite($controller, "        $"."nb_valides = 0;" . PHP_EOL);
            fwrite($controller, "        $"."messages= [];" . PHP_EOL);
            fwrite($controller, "        foreach ($"."request->selected_lines as $"."selected) {" . PHP_EOL);
            fwrite($controller, "            $".$model_snake." = ".$arguments['name']."::find($"."selected);" . PHP_EOL);
            fwrite($controller, "            if (isset($".$model_snake.")) {" . PHP_EOL);
            fwrite($controller, "                if ($".$model_snake."->est_valide !== 1) {" . PHP_EOL);
            fwrite($controller, "                    $".$model_snake."->est_valide = true ;" . PHP_EOL);
            fwrite($controller, "                    $".$model_snake."->statut = 'Confirmé' ;" . PHP_EOL);
            fwrite($controller, "                    $".$model_snake."->update();" . PHP_EOL);
            fwrite($controller, "                    $"."nb_valides++;" . PHP_EOL);
            fwrite($controller, "                    $"."messages = [" . PHP_EOL);
            fwrite($controller, "                        'severity' => 'success'," . PHP_EOL);
            fwrite($controller, "                        'value' => $"."nb_valides.' lignes ont été validé'" . PHP_EOL);
            fwrite($controller, "                    ];" . PHP_EOL);
            fwrite($controller, "                }" . PHP_EOL);
            fwrite($controller, "            }" . PHP_EOL);
            fwrite($controller, "        }" . PHP_EOL);
            fwrite($controller, "        return $"."this->sendApiResponse([], $"."messages);" . PHP_EOL);
            fwrite($controller, "    }" . PHP_EOL);
            fwrite($controller, PHP_EOL);
        }
        fwrite($controller, "    public function destroy_group(Request $"."request)" . PHP_EOL);
        fwrite($controller, "    {" . PHP_EOL);
        fwrite($controller, "        $"."key = 0;" . PHP_EOL);
        fwrite($controller, "        $"."nb_supprimes = 0;" . PHP_EOL);
        fwrite($controller, "        $"."messages= [];" . PHP_EOL);
        fwrite($controller, "        foreach ($"."request->selected_lines as $"."selected) {" . PHP_EOL);
        fwrite($controller, "            $".$model_snake." = ".$arguments['name']."::find($"."selected);" . PHP_EOL);
        fwrite($controller, "            if (isset($".$model_snake.")) {" . PHP_EOL);
        fwrite($controller, "                if ($".$model_snake."->est_valide == 1) {" . PHP_EOL);
        fwrite($controller, "                    $"."messages[$"."key] = [" . PHP_EOL);
        fwrite($controller, "                        'severity' => 'warn'," . PHP_EOL);
        fwrite($controller, "                        'value' => 'Impossible de supprimer ID0'.$"."selected" . PHP_EOL);
        fwrite($controller, "                    ];" . PHP_EOL);
        fwrite($controller, "                    $"."key++;" . PHP_EOL);
        fwrite($controller, "                }" . PHP_EOL);
        fwrite($controller, "                else {" . PHP_EOL);
        fwrite($controller, "                    $".$model_snake."->delete();" . PHP_EOL);
        fwrite($controller, "                    $"."nb_supprimes++;" . PHP_EOL);
        fwrite($controller, "                    $"."messages[$"."key] = [" . PHP_EOL);
        fwrite($controller, "                        'severity' => 'success'," . PHP_EOL);
        fwrite($controller, "                        'value' => $"."nb_supprimes.' lignes ont été supprimé'" . PHP_EOL);
        fwrite($controller, "                    ];" . PHP_EOL);
        fwrite($controller, "                }" . PHP_EOL);
        fwrite($controller, "            }" . PHP_EOL);
        fwrite($controller, "        }" . PHP_EOL);
        fwrite($controller, "        return $"."this->sendApiResponse([], $"."messages);" . PHP_EOL);
        fwrite($controller, "    }" . PHP_EOL);
        fwrite($controller, PHP_EOL);
        fwrite($controller, "}" . PHP_EOL);
        fclose($controller);
        echo 'Création terminé'. "\n". "\n";
    }
}
