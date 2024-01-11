<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
// use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class EdgesNoded extends Model
{
	// use SoftDeletes;
	/**
	 * The table associated with the model.
	 *
	 * @var string
	 */
	protected $table = 'edges_noded';

	/**
	 * The primary key associated with the table.
	 *
	 * @var string
	 */
	protected $primaryKey = 'id';

	/**
	 * Indicates if the model's ID is auto-incrementing.
	 *
	 * @var bool
	 */
	public $incrementing = true;

	/**
	 * The data type of the auto-incrementing ID.
	 *
	 * @var string
	 */
	// protected $keyType = 'string';

	/**
	 * Indicates if the model should be timestamped.
	 *
	 * @var bool
	 */
	public $timestamps = true;

	// const CREATED_AT = 'created_at';
	// const UPDATED_AT = 'updated_at';
	// const DELETED_AT = 'deleted_at';

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array
	 */
	// protected $fillable = ['name'];

	/**
	 * The attributes that aren't mass assignable.
	 *
	 * @var array
	 */
	protected $guarded = [];

	public function getVertex($lat, $lon)
	{
		return DB::select("SELECT
		v.id
	  FROM
		edges_noded_vertices_pgr AS v,
		edges_noded AS e
	  WHERE
		v.id = (SELECT
				  id
				FROM edges_noded_vertices_pgr
				ORDER BY the_geom <-> ST_SetSRID(ST_MakePoint(?,?), 4326) LIMIT 1)
		AND (e.source = v.id OR e.target = v.id) -- memastikan bahwa vertex yang didapat terdapat di source dan target
	  GROUP BY v.id;", [$lon, $lat]);
	}

	public function routeTransJakartaLine($startVertex, $endVertex): array
	{
		return DB::select("SELECT
            r.seq,
            r.node,
            r.edge,
            r.cost,
            ST_AsEWKT(e.wkb_geometry) as geom,
            eo.name
        FROM
            pgr_dijkstra('SELECT id,source,target,cost FROM edges_noded',
				$startVertex,
				$endVertex,
                FALSE) AS r
            INNER JOIN edges_noded AS e ON r.edge = e.id
            INNER JOIN edges AS eo ON e.old_id = eo.id;", []);
	}
}
