from fastapi import FastAPI
from pydantic import BaseModel
import joblib
import pandas as pd

app = FastAPI()

model = joblib.load("models/demand_forecasting_model.pkl")
features = joblib.load("models/features.pkl")


class ForecastInput(BaseModel):
    item_id: int
    dept_id: int
    cat_id: int
    store_id: int
    state_id: int
    sell_price: float
    day: int
    month: int
    year: int
    day_of_week: int
    is_weekend: int
    holiday_flag: int
    sales_lag_1: float
    sales_lag_7: float
    sales_lag_30: float
    rolling_mean_7: float
    rolling_mean_30: float
    snap_CA: int
    snap_TX: int
    snap_WI: int


@app.get("/")
def home():
    return {"message": "AI Demand Forecasting API Running"}


@app.post("/predict")
def predict(data: ForecastInput):

    input_data = data.dict()

    input_df = pd.DataFrame([input_data])

    input_df = input_df[features]

    prediction = model.predict(input_df)[0]

    predicted_demand = round(float(prediction), 2)

    return {
        "predicted_demand": predicted_demand,
        "recommended_stock": round(predicted_demand * 1.2, 2),
        "status": "Reorder Required" if predicted_demand > 10 else "Stock Sufficient"
    }