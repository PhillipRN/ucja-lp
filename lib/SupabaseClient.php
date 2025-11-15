<?php
/**
 * Supabase Client for PHP
 * Supabase REST API とのやり取りを行うクライアントクラス
 */

class SupabaseClient {
    private $url;
    private $apiKey;
    private $headers;

    public function __construct($url, $apiKey) {
        $this->url = rtrim($url, '/');
        $this->apiKey = $apiKey;
        $this->headers = [
            'apikey: ' . $this->apiKey,
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json',
            'Prefer: return=representation'
        ];
    }

    /**
     * SELECT クエリ
     */
    public function from($table) {
        return new SupabaseQueryBuilder($this->url, $this->headers, $table);
    }

    /**
     * INSERT
     */
    public function insert($table, $data) {
        $url = $this->url . '/rest/v1/' . $table;
        
        return $this->makeRequest('POST', $url, $data);
    }

    /**
     * UPDATE
     */
    public function update($table, $data, $conditions = []) {
        $url = $this->url . '/rest/v1/' . $table;
        
        if (!empty($conditions)) {
            $url .= '?' . http_build_query($conditions);
        }
        
        return $this->makeRequest('PATCH', $url, $data);
    }

    /**
     * DELETE
     */
    public function delete($table, $conditions = []) {
        $url = $this->url . '/rest/v1/' . $table;
        
        if (!empty($conditions)) {
            $url .= '?' . http_build_query($conditions);
        }
        
        return $this->makeRequest('DELETE', $url);
    }

    /**
     * RPC (stored procedure/function call)
     */
    public function rpc($functionName, $params = []) {
        $url = $this->url . '/rest/v1/rpc/' . $functionName;
        
        return $this->makeRequest('POST', $url, $params);
    }

    /**
     * HTTP リクエストを実行
     */
    private function makeRequest($method, $url, $data = null) {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        
        if ($data !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($error) {
            throw new Exception('cURL Error: ' . $error);
        }
        
        $result = json_decode($response, true);
        
        if ($httpCode >= 400) {
            $errorMessage = isset($result['message']) ? $result['message'] : 'Unknown error';
            throw new Exception('Supabase Error (HTTP ' . $httpCode . '): ' . $errorMessage);
        }
        
        return [
            'data' => $result,
            'status' => $httpCode,
            'success' => $httpCode >= 200 && $httpCode < 300
        ];
    }
}

/**
 * Supabase Query Builder
 * SELECT クエリの構築用
 */
class SupabaseQueryBuilder {
    private $url;
    private $headers;
    private $table;
    private $selectColumns = '*';
    private $filters = [];
    private $orderBy = [];
    private $limit = null;
    private $offset = null;
    private $countMode = false;

    public function __construct($url, $headers, $table) {
        $this->url = $url;
        $this->headers = $headers;
        $this->table = $table;
    }

    /**
     * SELECT カラムの指定
     * @param string $columns カラム名（デフォルト: '*'）
     * @param bool $count カウントモード（件数のみ取得）
     */
    public function select($columns = '*', $count = false) {
        $this->selectColumns = $columns;
        $this->countMode = $count;
        return $this;
    }

    /**
     * WHERE 条件（等しい）
     */
    public function eq($column, $value) {
        // Boolean値の処理
        if (is_bool($value)) {
            $value = $value ? 'true' : 'false';
        }
        $this->filters[] = $column . '=eq.' . urlencode($value);
        return $this;
    }

    /**
     * WHERE 条件（等しくない）
     */
    public function neq($column, $value) {
        // Boolean値の処理
        if (is_bool($value)) {
            $value = $value ? 'true' : 'false';
        }
        $this->filters[] = $column . '=neq.' . urlencode($value);
        return $this;
    }

    /**
     * WHERE 条件（より大きい）
     */
    public function gt($column, $value) {
        $this->filters[] = $column . '=gt.' . urlencode($value);
        return $this;
    }

    /**
     * WHERE 条件（以上）
     */
    public function gte($column, $value) {
        $this->filters[] = $column . '=gte.' . urlencode($value);
        return $this;
    }

    /**
     * WHERE 条件（より小さい）
     */
    public function lt($column, $value) {
        $this->filters[] = $column . '=lt.' . urlencode($value);
        return $this;
    }

    /**
     * WHERE 条件（以下）
     */
    public function lte($column, $value) {
        $this->filters[] = $column . '=lte.' . urlencode($value);
        return $this;
    }

    /**
     * WHERE 条件（LIKE）
     */
    public function like($column, $pattern) {
        $this->filters[] = $column . '=like.' . urlencode($pattern);
        return $this;
    }

    /**
     * WHERE 条件（IN）
     */
    public function in($column, $values) {
        $valueStr = '(' . implode(',', array_map('urlencode', $values)) . ')';
        $this->filters[] = $column . '=in.' . $valueStr;
        return $this;
    }

    /**
     * WHERE 条件（IS NULL）
     */
    public function isNull($column) {
        $this->filters[] = $column . '=is.null';
        return $this;
    }

    /**
     * ORDER BY
     * @param string $column カラム名
     * @param string|bool $direction 'asc'/'desc' または true(ASC)/false(DESC)
     */
    public function order($column, $direction = 'asc') {
        // Boolean値の処理
        if (is_bool($direction)) {
            $direction = $direction ? 'asc' : 'desc';
        }
        $this->orderBy[] = $column . '.' . strtolower($direction);
        return $this;
    }

    /**
     * LIMIT
     */
    public function limit($limit) {
        $this->limit = $limit;
        return $this;
    }

    /**
     * OFFSET
     */
    public function offset($offset) {
        $this->offset = $offset;
        return $this;
    }

    /**
     * 単一行を取得
     */
    public function single() {
        $this->limit(1);
        $result = $this->execute();
        
        if ($result['success'] && !empty($result['data'])) {
            return [
                'data' => $result['data'][0],
                'success' => true
            ];
        }
        
        return [
            'data' => null,
            'success' => false
        ];
    }

    /**
     * クエリを実行
     */
    public function execute() {
        $url = $this->url . '/rest/v1/' . $this->table;
        
        $queryParts = [];
        $queryParts[] = 'select=' . urlencode($this->selectColumns);
        
        // フィルターを追加（既にURLエンコード済みなのでそのまま使用）
        if (!empty($this->filters)) {
            foreach ($this->filters as $filter) {
                $queryParts[] = $filter;
            }
        }
        
        if (!empty($this->orderBy)) {
            $queryParts[] = 'order=' . implode(',', $this->orderBy);
        }
        
        if ($this->limit !== null) {
            $queryParts[] = 'limit=' . $this->limit;
        }
        
        if ($this->offset !== null) {
            $queryParts[] = 'offset=' . $this->offset;
        }
        
        $url .= '?' . implode('&', $queryParts);
        
        return $this->makeRequest('GET', $url);
    }

    /**
     * HTTP リクエストを実行
     */
    private function makeRequest($method, $url) {
        $ch = curl_init();
        
        $headers = $this->headers;
        // カウントモードの場合はヘッダーを追加
        if ($this->countMode) {
            $headers[] = 'Prefer: count=exact';
        }
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HEADER, true); // ヘッダーも取得
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($error) {
            throw new Exception('cURL Error: ' . $error);
        }
        
        // ヘッダーとボディを分離
        $headerStr = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);
        
        $result = json_decode($body, true);
        
        // Content-Rangeヘッダーから件数を取得
        $count = null;
        if ($this->countMode && preg_match('/content-range:\s*\d+-\d+\/(\d+|\*)/i', $headerStr, $matches)) {
            $count = $matches[1] !== '*' ? (int)$matches[1] : 0;
        }
        
        if ($httpCode >= 400) {
            $errorMessage = isset($result['message']) ? $result['message'] : 'Unknown error';
            throw new Exception('Supabase Error (HTTP ' . $httpCode . '): ' . $errorMessage);
        }
        
        return [
            'data' => $result,
            'status' => $httpCode,
            'success' => $httpCode >= 200 && $httpCode < 300,
            'count' => $count
        ];
    }
}

