<?php
	/**
	 * Array.php
	 * 
	 * This is a series of array utility functions, and a LINQ/SQL influenced
	 * array query builder, which you can use by calling 'query( $arr )'.
	 */
	
	/*
	 * This is mostly built, but incomplete, and needs more testing.
	 * 
	 * TODO
	 * 	= add 'sortDesc' and remove the desc/asc parameter
	 *  = allow sort to take multiple parameters for a stackable sort, like in SQL
	 *		i.e. sort( 'sex', 'age' )
	 *			sort( 'sex' )->sortDesc( 'age' )
	 *	  Both of those sort by age, and then sort females and males indevidually by
	 * 	  their age, like how SQL does it!
	 */

	/**
	 * Given an array, this creates a new query builder, and returns it.
	 * 
	 * Can be used in two ways. First you can pass in an array of elements,
	 * and those elements are placed inside the query builder. For example:
	 * 
	 * 	$query = query(array( 1, 2, 3, 4, 5 ));
	 * 
	 * The second way is to pass in multiple elements, and these are
	 * automatically turned into an array, and then put into the query builder.
	 * For example:
	 * 
	 *  // same as above
	 * 	$query = query( 1, 2, 3, 4, 5 );
	 * 
	 * Only numerically indexed arrays are supported.
	 * 
	 * @return A query builder for the given array.
	 */
	function query( $arr )
	{
		if ( func_num_args() > 1 ) {
			$arr = func_get_args();
		}

		return new QueryBuilder( $arr );
	}

	/**
	 * The core QueryBuilder class.
	 * 
	 * This is a LINQ/SQL style query building class for performing
	 * expressions on arrays.
	 * 
	 * It wraps an array, and then allows you to manipulate it accordingly,
	 * slicing, filtering, getting, ordering, mapping, where'ing, and so on.
	 * 
	 * The idea is that you wrap the array, apply your actions, then call
	 * 'gets' to get out the result. You can also call 'get' to get just one
	 * value.
	 */
	class QueryBuilder
	{
		private static function isDesc( $val ) {
			if ( $val === 'desc' ) {
				return true;
			} else if ( $val === 'asc' ) {
				return fasle;
			} else {
				throw new Exception("unknown value given, expecting 'desc' or 'asc' and got: " . $val );
			}
		}

		private $arr;

		/**
		 * Creates a new QueryBuilder which wraps the array given.
		 */
		public function __construct( $arr ) {
			if ( ! is_array($arr) ) {
				throw new Exception( "non-array given for query building" );
			}

			$this->arr = $arr;
		}

		/**
		 * @return The number of elements current selected at this point in the query.
		 */
		public function count() {
			return count($this->arr);
		}

		/**
		 * @return The number of elements current selected at this point in the query.
		 */
		public function size() {
			return count($this->arr);
		}

		/**
		 * @return The number of elements current selected at this point in the query.
		 */
		public function length() {
			return count($this->arr);
		}

		/**
		 * Gets and returns the element at the given index.
		 * If no index is given, it return the first element.
		 * 
		 * @param i The index of the element to get, defaults to 0.
		 * @return The element at that index, or null if it is not found.
		 */
		public function get( $i = 0 ) {
			if ( isset($arr[$i]) ) {
				return $arr[$i];
			} else {
				return null;
			}
		}

		/**
		 * The elements returned are as a result of all of the operations
		 * performed on this QueryBuilder.
		 * 
		 * @return An array of all of the resulting elements in this QueryBuilder.
		 */
		public function gets() {
			return $this->arr;
		}

		/**
		 * Given multiple arrays, this merges with each of them.
		 * 
		 * @return this query builder.
		 */
		public function merge() {
			$arr = $this->arr;

			for ( $i = 0; $i < func_num_args(); $i++ ) {
				$arg = func_get_arg($i);

				if ( is_array($arg) ) {
					$arr = array_merge( $arr, $arg );
				} else if ( $arg instanceof QueryBuilder ) {
					$arr = array_merge( $arr, $arg->gets() );
				} else {
					throw new Exception( "non-array given for merge" );
				}
			}

			$this->arr

			return $this;
		}

		/**
		 * Filters out elements from this array. How this is done is based on what val is.
		 * 
		 * If it's an array, then all of the values from the
		 * given array are removed from this one.
		 * 
		 *  // remove some odd numbers
		 * 	$query->filter(array(1, 3, 5, 7, 9));
		 * 
		 * If the value is a callback, then it is mapped against the array
		 * using 'array_filter', where each value is passed into it in turn.
		 * 
		 * If the function returns true, the value is kept, and otherwise
		 * it is removed.
		 * 
		 *  // remove all odd numbers
		 *  $query->filter( function($n) { return ($n % 2) === 0; } );
		 */
		public function filter($val) {
			$arr = $this->arr;

			if ( is_array($val) ) {
				$arr = array_values( array_diff($arr, $val) );
			} else if ( $val instanceof QueryBuilder ) {
				$arr = array_values( array_diff($arr, $val->gets()) );
			} else if ( $val instanceof Closure ) {
				$arr = array_filter( $arr, $val );
			} else {
				throw new Exception("unknown value given: " . $val);
			}

			$this->arr = $arr;

			return $this;
		}

		/**
		 * Any elements found in both this, and the given arrays, are kept.
		 * All other elements are thrown away.
		 * 
		 * @param other The other array to test elements against.
		 * @return This QueryBuilder object for method chaining.
		 */
		public function intersect( $other ) {
			$arr = $this->arr;

			if ( is_array($other) ) {
				$arr = array_values( array_intersect($arr, $other) );
			} else if ( $other instanceof QueryBuilder ) {
				$arr = array_values( array_intersect($arr, $other->gets()) );
			} else {
				throw new Exception("non-array given: " . $other);
			}

			$this->arr = $arr;
			return $this;
		}

		/**
		 * This is an alias for 'remove', and is exactly the same.
		 * 
		 * @param val The element to remove, multiple arguments are supported.
		 * @return This QueryBuilder for chaining.
		 */
		public function delete() {
			return $this->removeInner( func_get_args() );
		}

		/**
		 * Searches for, and removes, all of the elements given from this array.
		 * 
		 * @param val The element to remove, multiple arguments are supported.
		 * @return This QueryBuilder for chaining.
		 */
		public function remove() {
			return $this->removeInner( func_get_args() );
		}

		private function removeInner( $args )
		{
			$arr = $this->arr;

			for( $i=0; $i < count($args); $i++ ) {
				$val = $args[$i];

				for( $j=0; $j < count($arr); $j++ ) {
				  	if( $arr[$j] === $val ) {
				    	array_splice($arr, $j, 1)

				    	$j--;
				  	}
				}
			}

			$this->arr = $arr;

			return $this;
		}

		/**
		 * Maps a given callback against each element in this array.
		 * 
		 * @param callback The callback to apply to this array.
		 * @return This query builder.
		 */
		public function map( $callback ) {
			if ( !($callback instanceof Closure) ) {
				throw new Exception("lambda callback expected");
			}

			$this->arr = array_map( $this->arr, $callback );

			return $this;
		}

		/**
		 * Similar to slice, but the parameters are more SQL-like.
		 * 
		 * When used with one parameter, you are passing in the maximum
		 * number of elements to keep. For example:
		 * 
		 *  // keep the first 10 elements
		 * 	$query->limit( 10 );
		 * 
		 * When used with two elements, it's essentially slice,
		 * where the first parameter is the offset and the second is
		 * the limit.
		 * 
		 *  // keep elements from 5 till 15
		 * 	$query->limit( 5, 10 );
		 * 
		 * @return this QueryBuilder instance.
		 */
		public function limit($a) {
			if ( func_num_args() === 2 ) {
				$limit = func_get_arg(1);
				$offset = $a;
			} else {
				$limit = $a;
				$offset = 0;
			}
			
			$this->arr = array_slice( $this->arr, $offset, $limit );

			return $this;
		}

		/**
		 * Makes a slice from this array from the index given, onwards.
		 * 
		 * The offset is the index of where to start the slice,
		 * whilst an optional 'limit' can be provided stating how many
		 * elements to slice.
		 * 
		 * @param offset Where to start the offset.
		 * @param limit Optional, the number of elements to slice.
		 * @return this QueryBuilder.
		 */
		public function slice( $offset ) {
			$arr = $this->arr;

			if ( func_num_args() === 2 ) {
				$arr = array_slice( $arr, $offset, func_get_arg(1) );
			} else {
				$arr = array_slice( $arr, $offset );
			}

			$this->arr = $arr;

			return $this;
		}

		/**
		 * Reverses the order of the elements.
		 * 
		 * @return This query builder for chaining.
		 */
		public function reverse() {
			$this->arr = array_reverse( $this->arr );
			return $this;
		}

		/**
		 * This differs from the other sort, by allowing you to provide a PHP flag.
		 * For technical reasons a seperate method is required to support these flags.
		 * 
		 * At the time of writing these flags are:
		 * 	SORT_REGULAR
		 * 	SORT_NUMERIC
		 * 	SORT_STRING
		 * 	SORT_NATURAL
		 * 	SORT_FLAG_CASE
		 * 	SORT_LOCALE_STRING
		 * 
		 * See the PHP doc on sort for more information: http://php.net/manual/en/function.sort.php
		 * 
		 * @param flag Optional, the flag to use when sorting. By default this uses SORT_REGULAR, and pass in 'null' or 'false' to use this.
		 * @param direction Optional, 'asc' for ascending sorting, and 'desc' for descending sorting.
		 * @return This QuryBuilder for method chaining.
		 */
		public function sortPHP() {
			$arr = $this->arr;

			if ( func_num_args() === 0 ) {
				$arr = sort( $arr );
			} else {
				$flag = func_get_arg(1);

				if ( $flag === null || $flag === false ) {
					$flag = SORT_REGULAR;
				} else if ( ! (
						$flag === SORT_REGULAR 		||
						$flag === SORT_NUMERIC 		||
						$flag === SORT_STRING  		||
						$flag === SORT_NATURAL 		||
						$flag === SORT_FLAG_CASE 	||
						$flag === SORT_LOCALE_STRING
				) ) {
					throw new Exception("unknown flag given: " . $flag);
				}

				if ( func_num_args() > 1 && QueryBuilder::isDesc(func_get_arg(1)) ) {
					rsort( $arr, $flag );
				} else {
					sort( $arr, $flag );
				}
			}

			$this->arr = $arr;
			return $this;
		}

		/**
		 * An alias for 'sort'. This is useful to make code a bit more readable.
		 * For example:
		 * 
		 * 	query( $employees )->sortBy( 'name' );
		 *
		 * All of it's options and return values are the same as 'sort'.
		 */
		public function sortBy() {
			return $this->sortInner( func_get_args() );
		}

		/**
		 * Note that if you pass in a closure, and use 'desc' sorting,
		 * the the result is still reversed to the opposite of how you sorted it.
		 * 
		 * @param A property to use when sorting, or a callback to call.
		 * @param direction Optional, 'asc' for ascending sorting, and 'desc' for descending sorting.
		 * @return This QuryBuilder for method chaining.
		 */
		public function sort() {
			return $this->sortInner( func_get_args() );
		}

		private function sortInner( $args ) {
			$arr = $this->arr;
			$numArgs = count( $args );

			if ( $numArgs > 1 ) {
				$prop = $args[0];
				$isDesc = ( $numArgs > 1 ) && QueryBuilder::isDesc( $args[1] ) ;

				// if 'asc' or 'desc' was provided, sort accordingly,
				// otherwise fail
				if ( !$prop ) {
					if ( $numArgs > 1 ) {
						if ( $isDesc ) {
							rsort( $arr );
						} else {
							sort( $arr );
						}
					} else {
						throw new Exception("no property given for order");
					}
				// sort based on user function
				if ( $prop instanceof Closure ) {
					usort( $arr, $prop );

					if ( $isDesc ) {
						reverse( $arr );
					}
				// sort based on a named property
				) else {
					if ( $isDesc ) {
						usort( $arr, function($a, $b) use ( $prop ) {
							return ( $a[$prop] < $b[$prop] ) ?  1 : -1 ;
						});
					} else {
						usort( $arr, function($a, $b) use ( $prop ) {
							return ( $a[$prop] < $b[$prop] ) ? -1 :  1 ;
						});
					}
				}
			} else {
				sort( $arr );
			}

			$this->arr = $arr;
			return $this;
		}

		/**
		 * Shuffles the elements around, randomly.
		 * 
		 * @return this QueryBuilder for method chaining.
		 */
		public function shuffle() {
			shuffle( $this->arr );
			return $this;
		}

		/**
		 * Can be used in one of two ways.
		 * 
		 * First you can use a named property comparison, just like 'equal'.
		 * For example:
		 * 
		 * 	query( $employees )->where( 'sex', 'male' );
		 * 
		 * Alternatively you can pass in a closure to do the same:
		 * 
		 * 	query( $employees )->where( function($employee) {
		 * 		return $employee->sex === 'male' ;
		 * 	});
		 * 
		 * @return This QueryBuilder for method chaining.
		 */
		public function where() {
			if ( func_num_args() === 0 ) {
				throw new Exception("Not enough arguments given");
			}

			if ( func_num_args() === 1 ) {
				$callback = $func_get_arg( 0 );

				if ( ! ($callback instanceof Closure) ) {
					throw new Exception("none closure given as the function callback");
				} else {
					$this->arr = array_filter( $this->arr, $callback );
				}

				return $this;
			} else {
				return $this->equal( func_get_arg(0), func_get_arg(1) );
			}
		}

		/**
		 * Keeps all values where the property of the element matches the value given.
		 * 
		 * If one value is given, then this removes all elements which don't match
		 * that one value.
		 * 
		 * If two parameters are given, it will keep all elements where the property
		 * of each element matches the value given.
		 *
		 * @return This QueryBuilding for method chaining.
		 */
		public function equal( $property ) {
			$arr = $this->arr;

			if ( func_num_args() === 2 ) {
				$value = func_get_arg(1);

				$arr = array_filter( $arr, function($e) use ($property, $value) {
					return $e[$property] === $value;
				} );
			} else {
				for( $i=0; $i < count($arr); $i++ ) {
				  	if (!( $arr[$i] === $property )) {
				    	$arr = array_splice($arr, $i, 1)

				    	$i--;
				  	}
				}
			}

			$this->arr = $arr;
			
			return $this;
		}

		/**
		 * Works in exactly the same was as 'equal',
		 * only this keeps elements that are not equal.
		 */
		public function notEqual( $property ) {
			$arr = $this->arr;

			if ( func_num_args() === 2 ) {
				$value = func_get_arg(1);

				$arr = array_filter( $arr, function($e) use ($property, $value) {
					return $e[$property] !== $value;
				} );
			} else {
				for( $i=0; $i < count($arr); $i++ ) {
				  	if (!( $arr[$i] !== $property )) {
				    	$arr = array_splice($arr, $i, 1)

				    	$i--;
				  	}
				}
			}

			$this->arr = $arr;
			
			return $this;
		}

		/**
		 * Works in exactly the same was as 'equal',
		 * only this keeps elements that are greater than the condition given.
		 */
		public function greaterThan( $property ) {
			$arr = $this->arr;

			if ( func_num_args() === 2 ) {
				$value = func_get_arg(1);

				$arr = array_filter( $arr, function($e) use ($property, $value) {
					return $e[$property] > $value;
				} );
			} else {
				for( $i=0; $i < count($arr); $i++ ) {
				  	if (!( $arr[$i] > $property )) {
				    	$arr = array_splice($arr, $i, 1)

				    	$i--;
				  	}
				}
			}

			$this->arr = $arr;
			
			return $this;
		}

		/**
		 * Works in exactly the same was as 'equal',
		 * only this keeps elements that are greater than or equal to the condition given.
		 */
		public function greaterThanEqual( $property ) {
			$arr = $this->arr;

			if ( func_num_args() === 2 ) {
				$value = func_get_arg(1);

				$arr = array_filter( $arr, function($e) use ($property, $value) {
					return $e[$property] >= $value;
				} );
			} else {
				for( $i=0; $i < count($arr); $i++ ) {
				  	if (!( $arr[$i] >= $property )) {
				    	$arr = array_splice($arr, $i, 1)

				    	$i--;
				  	}
				}
			}

			$this->arr = $arr;
			
			return $this;
		}

		/**
		 * Works in exactly the same was as 'equal',
		 * only this keeps elements that are less than the condition given.
		 */
		public function lessThan( $property ) {
			$arr = $this->arr;

			if ( func_num_args() === 2 ) {
				$value = func_get_arg(1);

				$arr = array_filter( $arr, function($e) use ($property, $value) {
					return $e[$property] < $value;
				} );
			} else {
				for( $i=0; $i < count($arr); $i++ ) {
				  	if (!( $arr[$i] < $property )) {
				    	$arr = array_splice($arr, $i, 1)

				    	$i--;
				  	}
				}
			}

			$this->arr = $arr;
			
			return $this;
		}

		/**
		 * Works in exactly the same was as 'equal',
		 * only this keeps elements that are less than or equal to the condition given.
		 */
		public function lessThanEqual( $property ) {
			$arr = $this->arr;

			if ( func_num_args() === 2 ) {
				$value = func_get_arg(1);

				$arr = array_filter( $arr, function($e) use ($property, $value) {
					return $e[$property] <= $value;
				} );
			} else {
				for( $i=0; $i < count($arr); $i++ ) {
				  	if (!( $arr[$i] <= $property )) {
				    	$arr = array_splice($arr, $i, 1)

				    	$i--;
				  	}
				}
			}

			$this->arr = $arr;
			
			return $this;
		}
	}