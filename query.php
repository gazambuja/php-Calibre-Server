<?php
	/*
		This is a derived work.
		Derived from: 	Calibre, licensed under GPL v3
						http://calibre-ebook.com

		Changes:
		license:	 GPL v3
		copyright:	 2010, Charles Haley
	                 http://charles.haleys.org
	*/

	define('AND_TOKEN', 1);
	define('OR_TOKEN', 2);
	define('NOT_TOKEN', 3);
	define('VALUE_TOKEN', 4);
	define('EOF_TOKEN', 5);

	/*
	 * A node in the parse tree. The language is an expression language,
	 * so all nodes can be equal. Unary nodes simply have the right side NULL.
	 */
	class Node {
		function __construct($node_type, $left, $right, $leaf) {
			$this->node_type = $node_type;
			$this->left = $left;
			$this->right = $right;
			$this->leaf = $leaf;
		}
	}

	/*
	 * The leaf (value) node. This node knows how to test a field/value pair
	 * against the test specified in the search.
	 */
	class Leaf {
		function __construct($prefix, $match_type, $test) {
			$this->field = $prefix;
			$this->match_type = $match_type;
			$this->test = $test;
			$this->value = false;
		}

		function set($val) {
			$this->value = $val;
		}

		function match_value($val) {
			// test for field exists
			if ($this->test == 'true') {
				// special case test for empty strings, which match false
				if (!isset($val) || (is_string($val) && strlen($val) == 0))
					return false;
				// field is defined, so true test is true
				return true;
			}
			if ($this->test == 'false') {
				// special case test for empty strings, which match false
				if (!isset($val) || (is_string($val) && strlen($val) == 0))
					return true;
				// field is defined, so false test is false
				return false;
			}

			// Turn bools into strings. Yes, these should be translatable
			if (is_bool($val))
				$val = $val ? 'Yes' : 'No';
			if ($this->match_type == '=') {
				if (strcasecmp($val, $this->test) == 0)
					return true;
			} else if ($this->match_type == '~') {
				if (substr($this->test, 0, 1) == '/') {
					if (preg_match("$this->test", $val))
						return true;
				} else {
					if (preg_match("/$this->test/i", $val))
						return true;
				}
			} else if (stristr($val, $this->test)) {
				return true;
			}
			return NULL;
		}

		function match($key, $val) {
			if ($this->field)
				if ($this->field != $key)
					return NULL;
			if (is_array($val)) {
				foreach ($val as $v) {
					if ($key == 'formats')
						$a = $this->match_value($v['format']);
					else
						$a = $this->match_value($v);
					if (isset($a))
						return $a;
				}
				return NULL;
			}
			return $this->match_value($val);
		}
	}

	/*
	 * Representation of a compiled query. 'Compiles' the query + restriction
	 * into a parse tree and a compact node-match specification.
	 */
	class Query {

		function __construct($restriction, $query_string, $field_metadata) {
			$this->fm = $field_metadata;
			$this->token_value = '';
			$this->leaves = array();
			$this->error_message = '';
			if ($query_string) {
				$this->query = $query_string;
				try {
					$query_tree = $this->expression();
				} catch (Exception $e) {
					$this->error_message = 'Query error: ' . $e->getMessage();
					$query_tree = NULL;
				}
			} else
				$query_tree = NULL;

			if ($restriction) {
				$this->query = $restriction;
				try {
					$restriction_tree = $this->expression();
				} catch (Exception $e) {
					$this->error_message = 'Restriction error: ' . $e->getMessage();
					$restriction_tree = NULL;
				}
			} else
				$restriction_tree = NULL;

			if ($query_tree && $restriction_tree)
				return $this->parse_tree = new Node(AND_TOKEN, $restriction_tree, $query_tree, NULL);
			if ($query_tree)
				return $this->parse_tree = $query_tree;
			if ($restriction_tree)
				return $this->parse_tree = $restriction_tree;
			return $this->parse_tree = NULL;
		}

		function is_empty() {
			return !isset($this->parse_tree);
		}

		/*
		 * Prepare to evaluate a query for a book. The main work is to create
		 * an array of value nodes that we need to look for. The search routine
		 * must call this method, then eval_field on each book field, then
		 * evaluate.
		 */
		function prepare() {
			$this->leaves_to_eval = array();
			foreach ($this->leaves as $leaf) {
				$leaf->set(false);
				$this->leaves_to_eval[] = $leaf;
			}
		}

		/*
		 * Check if a field + value match one of the search's value tests.
		 * Return true if no tests remain (small optimization).
		 */
		function eval_fields($book)  {
			/*
			 * First check all named fields.
			 */
			foreach ($this->leaves as $leaf) {
				if (!$leaf->field)
					continue;
				if (!array_key_exists($leaf->field, $book))
					continue;
				$ans = $leaf->match($leaf->field, $book[$leaf->field]);
				if (isset($ans)) {
					$leaf->set($ans);
					unset($this->leaves_to_eval[$leaf->field]);
					continue;
				}
			}
			// No fields left? Return
			if (count($this->leaves_to_eval) == 0)
				return;
			/*
			 * Now check unnamed fields.
			 */
			foreach ($book as $k => $v) {
				// Don't bother to test NULL values
				if (!isset($v))
					continue;
				foreach ($this->leaves as $dex =>$leaf) {
					if (!$leaf->value) {
						$ans = $leaf->match($k, $v);
						if (isset($ans)) {
							/*
							 * We have an answer for this field test. Don't
							 * check it against any more fields.
							 */
							$leaf->set($ans);
							unset($this->leaves_to_eval[$dex]);
							continue;
						}
					}
				}
				// we are done if no fields left to check
				if (count($this->leaves_to_eval) == 0)
					return;
			}
		}

		/*
		 * Evaluate the parse tree. This assumes that the leaf (value) nodes
		 * have already been set by eval_field.
		 */
		function evaluate() {
			/*
			 * First loop through the tests, looking for :false tests that
			 * were not removed. If one is there, it means that the field was
			 * not defined in the book and therefore has a value of true.
			 */

			foreach ($this->leaves_to_eval as $key => $leaf) {
				if ($leaf->test == 'false') {
					$leaf->set(true);
					unset($this->leaves_to_eval[$key]);
				}
			}
			return $this->_eval($this->parse_tree);
		}

		/*
		 * Recursively walk the parse tree, computing the true/false value for
		 * this book.
		 */
		function _eval($node) {
			if ($node->node_type == AND_TOKEN)
				return $this->_eval($node->left) & $this->_eval($node->right);
			if ($node->node_type == OR_TOKEN)
				return $this->_eval($node->left) | $this->_eval($node->right);
			if ($node->node_type == NOT_TOKEN)
				return !$this->_eval($node->left);
			if ($node->node_type == VALUE_TOKEN){
				return $node->leaf->value;
			}
			throw new Exception('Unknown node in query eval');
		}

		/*
		 * A basic lexical analyser for the query language.
		 */
		function lex($pop_token) {
			$this->query = trim ($this->query);
			if (strlen($this->query) == 0)
				return EOF_TOKEN;
			if (substr($this->query, 0, 1) == '(') {
				if ($pop_token)
					$this->query = substr($this->query, 1);
				return '(';
			}
			if (substr($this->query, 0, 1) == ')') {
				if ($pop_token)
					$this->query = substr($this->query, 1);
				return ')';
			}
			if (preg_match('/^and(\s|\()/i', $this->query)) {
				if ($pop_token)
					$this->query = substr($this->query, 3);
				return AND_TOKEN;
			}
			if (preg_match('/^or(\s|\()/i', $this->query)) {
				if ($pop_token)
					$this->query = substr($this->query, 2);
				return OR_TOKEN;
			}
			if (preg_match('/^not(\s|\()/i', $this->query)) {
				if ($pop_token)
					$this->query = substr($this->query, 3);
				return NOT_TOKEN;
			}
			if (preg_match('/^(\S*?".+?")([ \(\)].*|$)/i', $this->query, $m)) {
				$this->token_value = $m[1];
				if ($pop_token)
					$this->query = $m[2];
				return VALUE_TOKEN;
			}
			if (preg_match('/^(.+?)([ \(\)].*|$)/i', $this->query, $m)) {
				$this->token_value = $m[1];
				if ($pop_token)
					$this->query = $m[2];
				return VALUE_TOKEN;
			}
			throw new Exception('Lex didn\'t find something useful');
		}

		/*
		 * Recursive descent parser. Top-most node is OR.
		 */
		function expression() {
			$left = $this->term();
			while ($this->lex(false) == OR_TOKEN) {
				$this->lex(true);
				$right = $this->term();
				$left = new Node(OR_TOKEN, $left, $right, NULL);
			}
			return $left;
		}

		/*
		 * Deal with AND.
		 */
		function term() {
			$left = $this->factor();
			for (;;) {
				$token = $this->lex(false);
				if ($token != AND_TOKEN & $token != VALUE_TOKEN)
					return $left;
				if ($token == AND_TOKEN)
					$this->lex(true);
				$right = $this->factor();
				$left = new Node(AND_TOKEN, $left, $right, NULL);
			}
			return $left;
		}

		/*
		 * Deal with NOT and value tests
		 */
		function factor() {
			$token = $this->lex(false);
			if ($token == '(') {
				$token = $this->lex(true);
				$tree = $this->expression();
				if ($this->lex(true) != ')')
					throw new Exception('Parse error: closing parenthesis expected');
				return $tree;
			}
			$token = $this->lex(true);
			if ($token == NOT_TOKEN)
				return new Node(NOT_TOKEN, $this->factor(), NULL, NULL);

 			if ($token != VALUE_TOKEN)
					throw new Exception('Parse error: value token expected');

			$t = $this->token_value;
			if (preg_match('/((.*?):)?([=~]?)("?)(.*?)(\4)$/', $t, $m)) {
				$prefix = $this->fm->search_term_to_field_key(strtolower($m[2]));
				if ($prefix && !$this->fm->key_exists($prefix)) {
					throw new Exception("Unknown metadata field: $prefix");
				}
				$match_type = $m[3];
				$value = $m[5];
			} else
				throw new Exception('value specifier $t not understood');

			$leaf = new Leaf($prefix, $match_type, $value);
			$left = new Node(VALUE_TOKEN, NULL, NULL, $leaf);
			$this->leaves[] = $leaf;
			return $left;
		}

		/*
		 * For debugging
		 */
		function print_tree() {
			$this->_print_tree('', $this->parse_tree);
		}

		function _print_tree($indent, $node) {
			if (!isset($node) || $node == NULL)
				return;
			print "$indent$node->node_type: ";
			switch ($node->node_type) {
				case AND_TOKEN:
					print 'AND';
					break;
				case OR_TOKEN:
					print 'OR';
					break;
				case NOT_TOKEN:
					print 'NOT';
					break;
			}
			if ($node->leaf)
				print $node->leaf->field . ':' . $node->leaf->test;
			print "<br>";
			$this->_print_tree($indent . '&nbsp;&nbsp;', $node->left);
			$this->_print_tree($indent . '&nbsp;&nbsp;', $node->right);

		}
	}
?>