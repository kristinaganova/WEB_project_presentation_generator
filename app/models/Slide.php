<?php

class Slide extends Model
{
    public function __construct()
    {
        parent::__construct();
        $this->ensureSlideOrderColumn();
    }

    private function ensureSlideOrderColumn()
    {
        try {
            $sql = "SHOW COLUMNS FROM slides LIKE 'slide_order'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            
            if ($stmt->rowCount() === 0) {
                error_log("slide_order column does not exist, creating it...");
                
                $alterSql = "ALTER TABLE slides ADD COLUMN slide_order INT DEFAULT 0";
                $alterStmt = $this->db->prepare($alterSql);
                $alterStmt->execute();
                
                $updateSql = "UPDATE slides SET slide_order = id WHERE slide_order = 0";
                $updateStmt = $this->db->prepare($updateSql);
                $updateStmt->execute();
                
                error_log("Successfully created and initialized slide_order column");
            }
        } catch (PDOException $e) {
            error_log("Error ensuring slide_order column: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
        }
    }

    public function getByPresentationId($presentationId)
    {
        try {
            error_log("Getting slides for presentation ID: " . $presentationId);
            
            $sql = "SELECT s.*, 
                    se.id as element_id, 
                    se.type as element_type, 
                    se.title as element_title, 
                    se.content as element_content, 
                    se.text as element_text, 
                    COALESCE(se.style, '{}') as element_style, 
                    se.element_order
                 FROM slides s
                 LEFT JOIN slide_elements se ON s.id = se.slide_id
                    WHERE s.presentation_id = :presentation_id 
                    ORDER BY s.slide_order ASC, se.element_order ASC";
                    
            error_log("SQL Query: " . $sql);
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['presentation_id' => $presentationId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            error_log("Found " . count($rows) . " rows");
            
            $slides = [];
            $currentSlide = null;
            
            foreach ($rows as $row) {
                error_log("Processing row: " . print_r($row, true));
                
                if ($currentSlide === null || $currentSlide['id'] !== $row['id']) {
                    if ($currentSlide !== null) {
                        $slides[] = $currentSlide;
                    }
                    
                    $currentSlide = [
                        'id' => $row['id'],
                        'presentation_id' => $row['presentation_id'],
                        'title' => $row['title'],
                        'slide_order' => (int)$row['slide_order'],
                        'layout' => $row['layout'],
                        'elements' => []
                    ];
                }
                
                if (!empty($row['element_id'])) {
                    $element = [
                        'id' => $row['element_id'],
                        'type' => $row['element_type'],
                        'title' => $row['element_title'] ?? '',
                        'content' => $row['element_content'] ?? '',
                        'text' => $row['element_text'] ?? '',
                        'style' => json_decode($row['element_style'], true) ?? [],
                        'element_order' => (int)($row['element_order'] ?? 0)
                    ];
                    
                    $currentSlide['elements'][] = $element;
                }
            }
            
            if ($currentSlide !== null) {
                $slides[] = $currentSlide;
            }
            
            usort($slides, function($a, $b) {
                return $a['slide_order'] - $b['slide_order'];
            });
            
            error_log("Final sorted slides array: " . print_r($slides, true));
            return $slides;
            
        } catch (PDOException $e) {
            error_log("Error getting slides: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            throw $e;
        }
    }

    public function create($data)
    {
        try {
            error_log("Attempting to create slide with data: " . print_r($data, true));
            error_log("Database connection details: " . DB_HOST . ", " . DB_NAME . ", " . DB_USER);
            
            $this->db->beginTransaction();
            
            $sql = "INSERT INTO slides (presentation_id, title, slide_order, layout) 
                    VALUES (:presentation_id, :title, :slide_order, :layout)";
            error_log("SQL for slide creation: " . $sql);
            
            $stmt = $this->db->prepare($sql);
            
            $slideOrder = $data['slide_order'] ?? $this->getNextOrder($data['presentation_id']);
            error_log("Using slide order: " . $slideOrder);
            
            $params = [
                'presentation_id' => $data['presentation_id'],
                'title' => $data['title'],
                'slide_order' => $slideOrder,
                'layout' => $data['layout']
            ];
            error_log("Parameters for slide creation: " . print_r($params, true));
            
            $result = $stmt->execute($params);
            error_log("Slide creation result: " . ($result ? "success" : "failed"));
            
            if (!$result) {
                error_log("PDO Error Info: " . print_r($stmt->errorInfo(), true));
                throw new Exception("Failed to create slide: " . implode(", ", $stmt->errorInfo()));
            }
            
            $slideId = $this->db->lastInsertId();
            error_log("Created slide with ID: " . $slideId);
            
            if (!empty($data['elements'])) {
                $sql = "INSERT INTO slide_elements (slide_id, element_order, type, title, content, text, style) 
                        VALUES (:slide_id, :element_order, :type, :title, :content, :text, :style)";
                error_log("SQL for element creation: " . $sql);
                
                $stmt = $this->db->prepare($sql);
                
                foreach ($data['elements'] as $order => $element) {
                    error_log("Creating element: " . print_r($element, true));
                    $params = [
                        'slide_id' => $slideId,
                        'element_order' => $order,
                        'type' => $element['type'],
                        'title' => $element['title'] ?? null,
                        'content' => $element['content'] ?? null,
                        'text' => $element['text'] ?? null,
                        'style' => json_encode($element['style'] ?? null)
                    ];
                    error_log("Parameters for element creation: " . print_r($params, true));
                    
                    $result = $stmt->execute($params);
                    error_log("Element creation result: " . ($result ? "success" : "failed"));
                    
                    if (!$result) {
                        error_log("PDO Error Info: " . print_r($stmt->errorInfo(), true));
                        throw new Exception("Failed to create element: " . implode(", ", $stmt->errorInfo()));
                    }
                }
            }
            
            $this->db->commit();
            error_log("Successfully created slide and its elements");
            return $slideId;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error creating slide: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            throw $e;
        }
    }

    public function getById($id)
{
    $sql = "SELECT * FROM slides WHERE id = :id";
    $stmt = $this->db->prepare($sql);
    $stmt->execute(['id' => $id]);
    $slide = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($slide) {
        $sql = "SELECT * FROM slide_elements WHERE slide_id = :slide_id ORDER BY element_order ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['slide_id' => $id]);
        $elements = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($elements as &$element) {
            $element['style'] = json_decode($element['style'] ?? '{}', true);
        }

        $slide['elements'] = $elements;
    }

    return $slide;
}

    public function update($id, $data)
    {
        try {
            error_log("Attempting to update slide with ID: " . $id);
            error_log("Update data: " . print_r($data, true));
            
            $this->db->beginTransaction();
            
            $sql = "UPDATE slides SET title = :title, layout = :layout WHERE id = :id";
            error_log("SQL for slide update: " . $sql);
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                'id' => $id,
                'title' => $data['title'],
                'layout' => $data['layout']
            ]);
            
            if (!$result) {
                error_log("PDO Error Info: " . print_r($stmt->errorInfo(), true));
                throw new Exception("Failed to update slide: " . implode(", ", $stmt->errorInfo()));
            }
            
            $sql = "DELETE FROM slide_elements WHERE slide_id = :slide_id";
            error_log("SQL for deleting old elements: " . $sql);
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute(['slide_id' => $id]);
            
            if (!$result) {
                error_log("PDO Error Info: " . print_r($stmt->errorInfo(), true));
                throw new Exception("Failed to delete old elements: " . implode(", ", $stmt->errorInfo()));
            }
            
            if (!empty($data['elements'])) {
                $sql = "INSERT INTO slide_elements (slide_id, element_order, type, title, content, text, style) 
                        VALUES (:slide_id, :element_order, :type, :title, :content, :text, :style)";
                error_log("SQL for element creation: " . $sql);
                
                $stmt = $this->db->prepare($sql);
                
                foreach ($data['elements'] as $order => $element) {
                    error_log("Creating element: " . print_r($element, true));
                    $params = [
                        'slide_id' => $id,
                        'element_order' => $order,
                        'type' => $element['type'],
                        'title' => $element['title'] ?? null,
                        'content' => $element['content'] ?? null,
                        'text' => $element['text'] ?? null,
                        'style' => json_encode($element['style'] ?? null)
                    ];
                    error_log("Parameters for element creation: " . print_r($params, true));
                    
                    $result = $stmt->execute($params);
                    error_log("Element creation result: " . ($result ? "success" : "failed"));
                    
                    if (!$result) {
                        error_log("PDO Error Info: " . print_r($stmt->errorInfo(), true));
                        throw new Exception("Failed to create element: " . implode(", ", $stmt->errorInfo()));
                    }
                }
            }
            
            $this->db->commit();
            error_log("Successfully updated slide and its elements");
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error updating slide: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            throw $e;
        }
    }

    public function delete($id)
    {
        try {
            error_log("Attempting to delete slide with ID: " . $id);
            
            $this->db->beginTransaction();
            
            $sql = "DELETE FROM slide_elements WHERE slide_id = :slide_id";
            error_log("SQL for deleting elements: " . $sql);
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute(['slide_id' => $id]);
            
            if (!$result) {
                error_log("PDO Error Info: " . print_r($stmt->errorInfo(), true));
                throw new Exception("Failed to delete elements: " . implode(", ", $stmt->errorInfo()));
            }
            
            $sql = "DELETE FROM slides WHERE id = :id";
            error_log("SQL for deleting slide: " . $sql);
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute(['id' => $id]);
            
            if (!$result) {
                error_log("PDO Error Info: " . print_r($stmt->errorInfo(), true));
                throw new Exception("Failed to delete slide: " . implode(", ", $stmt->errorInfo()));
            }
            
            $this->db->commit();
            error_log("Successfully deleted slide and its elements");
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error deleting slide: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            throw $e;
        }
    }

    public function updateOrder($slideId, $newOrder)
    {
        try {
            error_log("Updating slide order in database. Slide ID: $slideId, New order: $newOrder");
            
            $this->db->beginTransaction();
            
            try {
                $presentationSql = "SELECT presentation_id FROM slides WHERE id = :id";
                $presentationStmt = $this->db->prepare($presentationSql);
                $presentationStmt->execute(['id' => $slideId]);
                $presentationId = $presentationStmt->fetchColumn();
                
                if (!$presentationId) {
                    error_log("Slide with ID $slideId not found");
                    $this->db->rollBack();
                    return false;
                }
                
                $currentOrderSql = "SELECT slide_order FROM slides WHERE id = :id";
                $currentOrderStmt = $this->db->prepare($currentOrderSql);
                $currentOrderStmt->execute(['id' => $slideId]);
                $currentOrder = (int)$currentOrderStmt->fetchColumn();
                
                error_log("Current order: $currentOrder, New order: $newOrder");
                
                if ($currentOrder === $newOrder) {
                    error_log("Order unchanged, no update needed");
                    $this->db->commit();
                    return true;
                }
                
                if ($currentOrder < $newOrder) {
                    $sql = "UPDATE slides 
                           SET slide_order = slide_order - 1 
                           WHERE presentation_id = :presentation_id
                           AND slide_order > :current_order 
                           AND slide_order <= :new_order";
                    $stmt = $this->db->prepare($sql);
                    $stmt->execute([
                        'presentation_id' => $presentationId,
                        'current_order' => $currentOrder,
                        'new_order' => $newOrder
                    ]);
                } else {
                    $sql = "UPDATE slides 
                           SET slide_order = slide_order + 1 
                           WHERE presentation_id = :presentation_id
                           AND slide_order >= :new_order 
                           AND slide_order < :current_order";
                    $stmt = $this->db->prepare($sql);
                    $stmt->execute([
                        'presentation_id' => $presentationId,
                        'current_order' => $currentOrder,
                        'new_order' => $newOrder
                    ]);
                }
                
                $updateSql = "UPDATE slides SET slide_order = :new_order WHERE id = :id";
                $updateStmt = $this->db->prepare($updateSql);
                $result = $updateStmt->execute([
                    'id' => $slideId,
                    'new_order' => $newOrder
                ]);
                
                if (!$result) {
                    error_log("Failed to update slide order. PDO Error Info: " . print_r($updateStmt->errorInfo(), true));
                    $this->db->rollBack();
                    return false;
                }
                
                $checkSql = "SELECT slide_order FROM slides WHERE id = :id";
                $checkStmt = $this->db->prepare($checkSql);
                $checkStmt->execute(['id' => $slideId]);
                $updatedOrder = (int)$checkStmt->fetchColumn();
                
                if ($updatedOrder !== $newOrder) {
                    error_log("Order update verification failed. Expected: $newOrder, Got: $updatedOrder");
                    $this->db->rollBack();
                    return false;
                }
                
                $this->db->commit();
                error_log("Successfully updated slide order");
                return true;
                
            } catch (PDOException $e) {
                $this->db->rollBack();
                error_log("Error in transaction: " . $e->getMessage());
                error_log("Stack trace: " . $e->getTraceAsString());
                throw $e;
            }
            
        } catch (PDOException $e) {
            error_log("Error updating slide order: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return false;
        }
    }

    private function getNextOrder($presentationId)
    {
        try {
            error_log("Getting next order for presentation ID: " . $presentationId);
            
            $sql = "SELECT MAX(slide_order) as max_order FROM slides WHERE presentation_id = :presentation_id";
            error_log("SQL for getting max order: " . $sql);
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute(['presentation_id' => $presentationId]);
            
            if (!$result) {
                error_log("PDO Error Info: " . print_r($stmt->errorInfo(), true));
                throw new Exception("Failed to get max order: " . implode(", ", $stmt->errorInfo()));
            }
            
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $nextOrder = ($row['max_order'] ?? 0) + 1;
            
            error_log("Next order will be: " . $nextOrder);
            return $nextOrder;
            
        } catch (Exception $e) {
            error_log("Error getting next order: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            throw $e;
        }
    }

    public function addElement($slideId, $type, $content, $title = null)
    {
        try {
            $sql = "SELECT COALESCE(MAX(element_order), -1) + 1 as next_order 
                    FROM slide_elements 
                    WHERE slide_id = :slide_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['slide_id' => $slideId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $nextOrder = $result['next_order'];

            $sql = "INSERT INTO slide_elements (slide_id, type, content, title, element_order) 
                    VALUES (:slide_id, :type, :content, :title, :element_order)";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                'slide_id' => $slideId,
                'type' => $type,
                'content' => $content,
                'title' => $title,
                'element_order' => $nextOrder
            ]);

            if (!$result) {
                error_log("PDO Error Info: " . print_r($stmt->errorInfo(), true));
                throw new Exception("Failed to add element: " . implode(", ", $stmt->errorInfo()));
            }

            return $this->db->lastInsertId();
        } catch (Exception $e) {
            error_log("Error adding element: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            throw $e;
        }
    }
} 