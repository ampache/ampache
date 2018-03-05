<?php

namespace App\Http\Controllers;

use App\Models\Catalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CatalogController extends Controller
{
    protected $catalogs;
    
    public function __construct(Catalog $catalogs)
    {
        $this->catalogs = $catalogs;
    }
    
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $catalogs = $this->catalogs->paginate(15);
        return view('catalogs.index')->with('catalogs', $catalogs);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $catalogs = array();
        $tables = DB::select("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA='ampache' AND TABLE_NAME LIKE 'catalog\_%'");
        foreach ($tables as $type) {
            $class_name = '\\Modules\\Catalogs\\' . ucfirst(substr($type->TABLE_NAME, 8)) . '\\' . ucfirst($type->TABLE_NAME) ;
            $catalogs[] = new $class_name();
        }
        return view('catalogs.create', compact('catalogs'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Catalog  $catalog
     * @return \Illuminate\Http\Response
     */
    public function show(Catalog $catalog)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Catalog  $catalog
     * @return \Illuminate\Http\Response
     */
    public function edit(Catalog $catalog)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Catalog  $catalog
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Catalog $catalog)
    {
        //
    }

    public function action($action) {
        
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Catalog  $catalog
     * @return \Illuminate\Http\Response
     */
    public function destroy(Catalog $catalog)
    {
        //
    }
    
    /**
     * Show dropdown catalog types.
     * @param string $divback
     */
    public function show_catalog_types($divback = 'catalog_type_fields')
    {
        echo "<script language=\"javascript\" type=\"text/javascript\">" .
            "var type_fields = new Array();" .
            "type_fields['none'] = '';";
        $seltypes = '<option value="none">[Select]</option>';
        $types    = self::get_catalog_types();
        foreach ($types as $type) {
            $catalog = self::create_catalog_type($type);
            if ($catalog->is_installed()) {
                $seltypes .= '<option value="' . $type . '">' . $type . '</option>';
                echo "type_fields['" . $type . "'] = \"";
                $fields = $catalog->catalog_fields();
                $help   = $catalog->get_create_help();
                if (!empty($help)) {
                    echo "<tr><td></td><td>" . $help . "</td></tr>";
                }
                foreach ($fields as $key => $field) {
                    echo "<tr><td style='width: 25%;'>" . $field['description'] . ":</td><td>";
                    
                    switch ($field['type']) {
                        case 'checkbox':
                            echo "<input type='checkbox' name='" . $key . "' value='1' " . (($field['value']) ? 'checked' : '') . "/>";
                            break;
                        default:
                            echo "<input type='" . $field['type'] . "' name='" . $key . "' value='" . $field['value'] . "' />";
                            break;
                    }
                    echo "</td></tr>";
                }
                echo "\";";
            }
        }
        
        echo "function catalogTypeChanged() {" .
            "var sel = document.getElementById('catalog_type');" .
            "var seltype = sel.options[sel.selectedIndex].value;" .
            "var ftbl = document.getElementById('" . $divback . "');" .
            "ftbl.innerHTML = '<table class=\"tabledata\" cellpadding=\"0\" cellspacing=\"0\">' + type_fields[seltype] + '</table>';" .
            "} </script>" .
            "<select name=\"type\" id=\"catalog_type\" onChange=\"catalogTypeChanged();\">" . $seltypes . "</select>";
    }
    
    
}
