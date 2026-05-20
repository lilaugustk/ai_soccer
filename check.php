<?php echo json_encode(App\Models\FootballEventMetadata::where('event_id', 3667)->first()); echo \
\n---\n\; echo json_encode(App\Models\FootballEventPrediction::where('event_id', 3667)->first());
