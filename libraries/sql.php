<?php
// 
class DB {
	/**
	* SQLite DB manipualtor.
	* Secure and easy to use library for SQL DB communication.
	*/
	
	protected $db;
	
	public function __construct($path){
		if (file_exists($path)){
			$this->db = new PDO('sqlite:'.$path);
			$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);			
		}
		else {
			throw new Exception('Database Does Not Exist');
		}
	}
	
	private function secQuery($query, $params){
		/**
		* Generic secure query function
		* @param $query: sql query
		* @param $params: Desired array of parameters for binding
		* @return : array containing query status and statement handle for further manipulation
		*/
		// echo $query.'<br>';
		// var_dump($params);
		// echo '<br>';
		$stmt = $this->db->prepare($query);
		
		$result = $stmt->execute($params);
		// try{
		// 	$result = $stmt->execute($params);
		// }
		// catch(Exception $e){$result = False;}
		
		return array($result, $stmt);
	}
	
	private static function joinToString($array, $level1_conjunction, $level2_conjunction){
		
		#TODO: Allow injecting string portions to the resulted query
		
		/**
		* Converts a key-value array into string with specified conjunctions
		* You can use multiple conjunctions at different places if you put them inside the key-value array as 'conjunction'=>'anything'
		* @param $array: key-value array
		* @param $level1_conjunction: The default conjunction to be used everywhere unless specific conjunction is found in the passed array
		* @param $level2_conjunction: The default relation to be used everywhere unless specific conjunction is found in the passed array
		* @return : Formatted string in the form "key1<relation>value1 <conjunction> key2<relation>value2" and so on...
		* 
		* @example for defaults(conjunction=', ', relation='='): joinToString(array('school'=>'engineering university', 'age'=>'20'), ', ', '=')
		* returns 'school=?, password=?'
		* 
		* @example for overriding defaults(conjunction='and' , relation='>'): joinToString(array('age'=>'40', 'balance'=>'1000000', 'years_of_experience'=>'10'), 'and', '>')
		* returns 'age > 40 and balance > 1000000 and years_of_experience > 10'
		* 
		* @example for adding one-time conjunctions and relations: joinToString(array('username'=>'blah', 'or', 'age'=>array('<=','20')), ', ', '=')
		* returns 'username=? or age<=?'
		*/
		$level2_conjunction = ' '.trim($level2_conjunction).' ';
		if($level1_conjunction != ', ') $level1_conjunction = ' '.trim($level1_conjunction).' ';
		
		$result = '';
		$one_time_conjunction = null;
		foreach($array as $key => $value){
			if(is_numeric($key)) $one_time_conjunction = $value;
			else {
				// Add conjunction
				if (strlen($result)){
					$result .= $one_time_conjunction ? ' '.$one_time_conjunction.' ' : $level1_conjunction;
					$one_time_conjunction = null;
				}
				// Identify current relation
				
				$relation = is_array($value) ? ' '.trim($value[0]).' ' : $level2_conjunction;
				$result .= $key.$relation.'?';
			}
		}
		return $result;
	}

	private static function formatFields($fields){
		#TODO Merge this with joinToString in a better way
		$result = '';
		foreach($fields as $table => $field){
			$result .= $table.'.'.$field.', ';
		}
		return substr($result, 0, -1);
	}
	
	private static function extractParams($array){
		$result = array();
		foreach($array as $key => $value){
			if (!is_numeric($key)){
				if(is_array($value)) array_push($result, $value[1]);
				else array_push($result, $value);
			}
		}
		return $result;
	}
	
	public function select($fields, $table, $conditions=null){
		/**
		* Secure and easy-to-use select query
		* @param $table: database table to select from
		* @param $fields: fields to fetch from each matched record
		* @param $conditions: Matching conditions key-value pairs
		* @return : Array of all matched records
		*/
		
		$query = 'select '.implode(',', $fields).' from '.$table;
		$bind_params = null;
		
		if(is_array($conditions)){ // 
			#TODO: Validate key-value pair by checking that keys exist for all values from x0 to xn-1
			$bind_params = DB::extractParams($conditions);
			$query .= ' where '.DB::joinToString($conditions, 'and', '=');
		}
		return $this->secQuery($query, $bind_params)[1]->fetchAll(PDO::FETCH_ASSOC);
	}

	public function joinAndSelect($fields, $tables, $conditions){
		/**
		 * Method joins tables and select data from them
		 * @param $fields: key-value pairs (table=>array(fields in this table))
		 * @example $fields: array('users'=>array('username', 'age', 'phone'))
		 * @param $tables: key-value pairs (table=>joining_field)
		 * @example $tables: array('users'=>'_id', 'messages'=>'user_id')
		 * @param $conditions: MAtching conditions key-value pairs
		 * @return : Array of all matched records
		 */

		 $query = 'select '.DB::formatFields($fields).' from '.implode(' join ', array_keys($tables)).' on '.DB::joinToString($tables, 'and', '.') .' where '.DB::joinToString($conditions, 'and', '=');
		 $bind_params = DB::extractParams($conditions);
		 return $this->secQuery($query, $bind_params)[1]->fetchAll(PDO::FETCH_ASSOC);
	}
	
	public function insert($table, $fields, $values){
		/**
		* Secure and easy-to-use insert query
		* @param $table: database table to select from
		* @param $fields: columns to be filled
		* @param $values: values to be put at each column
		* @return : id of the inserted record
		* @return : false in case insert failed (uniqueness violated or not null condition violated etc...)
		*/
		
		$q_marks = '';
		$len = count($values);
		for($i = 0; $i < $len; $i++){
			if (strlen($q_marks))
			$q_marks .= ', ';
			$q_marks .= '?';
		}
		$query = 'insert into '.$table.' ('.implode(',', $fields).') values ('.$q_marks.')';
		
		if($this->secQuery($query, $values)[0])
		return $this->db->lastInsertId();
		else return false;
	}
	
	public function update($table, $new_values, $conditions){
		/**
		* Secure and easy-to-use update query
		* @param $table: database table to select from
		* @param $new_values: the new column-value combinations
		* @param $conditions: Matching conditions key-value pairs
		* @return : number of affected rows
		* @return : false in case insert failed (uniqueness violated or not null condition violated etc...)
		*/
		
		$new_values_str = DB::joinToString($new_values, ', ', '=');
		$conditions_str = ' where '.DB::joinToString($conditions, 'and', '=');
		
		$query = 'update '.$table.' set '.$new_values_str.$conditions_str;
		
		$values = array_merge(array_values($new_values), array_values($conditions));
		$result = $this->secQuery($query, $values);
		if($result[0])
		return $result[1]->rowCount();
		else return false;
	}
	
	public function delete($table, $conditions){
		/**
		* Secure and easy-to-use delete query
		* @param $table: database table to delete from
		* @param $conditions: Matching conditions key-value pairs
		* @return : number of affected rows
		* @return : false in case delete failed
		*/
		
		$cond_str = ' where '.DB::joinToString($conditions, 'and', '=');
		$query = 'delete from '.$table.$cond_str;
		$params = DB::extractParams($conditions);
		$result = $this->secQuery($query, $params);
		if($result[0])
		return $result[1]->rowCount();
		else return false;
	}
	
}

?>