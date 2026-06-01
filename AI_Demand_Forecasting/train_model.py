import os
import joblib
import numpy as np
import pandas as pd

from lightgbm import LGBMRegressor
from sklearn.metrics import mean_absolute_error, mean_squared_error
from sklearn.preprocessing import LabelEncoder


DATA_PATH = "data"
MODEL_PATH = "models"

os.makedirs(MODEL_PATH, exist_ok=True)


# -------------------------------
# 1. Load Dataset
# -------------------------------

print("Loading data...")

sales = pd.read_csv(f"{DATA_PATH}/sales_train_validation.csv")
calendar = pd.read_csv(f"{DATA_PATH}/calendar.csv")
prices = pd.read_csv(f"{DATA_PATH}/sell_prices.csv")


# -------------------------------
# 2. Use Small Sample First
# -------------------------------
# M5 dataset is very large, so start with limited products for testing.

sales = sales.head(1000)

id_columns = [
    "id", "item_id", "dept_id", "cat_id",
    "store_id", "state_id"
]

day_columns = [col for col in sales.columns if col.startswith("d_")]


# -------------------------------
# 3. Convert Wide Data to Long Data
# -------------------------------

print("Converting data format...")

sales_long = sales.melt(
    id_vars=id_columns,
    value_vars=day_columns,
    var_name="d",
    value_name="sales_quantity"
)


# -------------------------------
# 4. Merge Calendar Data
# -------------------------------

calendar = calendar[
    [
        "d", "date", "wm_yr_wk",
        "event_name_1", "event_type_1",
        "snap_CA", "snap_TX", "snap_WI"
    ]
]

df = sales_long.merge(calendar, on="d", how="left")


# -------------------------------
# 5. Merge Price Data
# -------------------------------

df = df.merge(
    prices,
    on=["store_id", "item_id", "wm_yr_wk"],
    how="left"
)


# -------------------------------
# 6. Basic Cleaning
# -------------------------------

print("Cleaning data...")

df["date"] = pd.to_datetime(df["date"])

df["sell_price"] = df["sell_price"].fillna(df["sell_price"].median())

df["event_name_1"] = df["event_name_1"].fillna("No Event")
df["event_type_1"] = df["event_type_1"].fillna("No Event")

df = df.sort_values(["id", "date"])


# -------------------------------
# 7. Feature Engineering
# -------------------------------

print("Creating features...")

df["day"] = df["date"].dt.day
df["month"] = df["date"].dt.month
df["year"] = df["date"].dt.year
df["day_of_week"] = df["date"].dt.dayofweek

df["is_weekend"] = df["day_of_week"].isin([5, 6]).astype(int)

df["holiday_flag"] = (df["event_name_1"] != "No Event").astype(int)


# Lag features
df["sales_lag_1"] = df.groupby("id")["sales_quantity"].shift(1)
df["sales_lag_7"] = df.groupby("id")["sales_quantity"].shift(7)
df["sales_lag_30"] = df.groupby("id")["sales_quantity"].shift(30)

# Rolling average features
df["rolling_mean_7"] = (
    df.groupby("id")["sales_quantity"]
    .shift(1)
    .rolling(window=7)
    .mean()
)

df["rolling_mean_30"] = (
    df.groupby("id")["sales_quantity"]
    .shift(1)
    .rolling(window=30)
    .mean()
)

df = df.dropna()


# -------------------------------
# 8. Encode Categorical Features
# -------------------------------

print("Encoding categorical features...")

categorical_cols = [
    "item_id", "dept_id", "cat_id",
    "store_id", "state_id",
    "event_name_1", "event_type_1"
]

encoders = {}

for col in categorical_cols:
    encoder = LabelEncoder()
    df[col] = encoder.fit_transform(df[col].astype(str))
    encoders[col] = encoder


# -------------------------------
# 9. Select Features and Target
# -------------------------------

features = [
    "item_id",
    "dept_id",
    "cat_id",
    "store_id",
    "state_id",
    "sell_price",
    "day",
    "month",
    "year",
    "day_of_week",
    "is_weekend",
    "holiday_flag",
    "sales_lag_1",
    "sales_lag_7",
    "sales_lag_30",
    "rolling_mean_7",
    "rolling_mean_30",
    "snap_CA",
    "snap_TX",
    "snap_WI"
]

target = "sales_quantity"

X = df[features]
y = df[target]


# -------------------------------
# 10. Time-Based Train/Test Split
# -------------------------------

print("Splitting data...")

split_date = df["date"].quantile(0.8)

train_data = df[df["date"] <= split_date]
test_data = df[df["date"] > split_date]

X_train = train_data[features]
y_train = train_data[target]

X_test = test_data[features]
y_test = test_data[target]


# -------------------------------
# 11. Train LightGBM Model
# -------------------------------

print("Training model...")

model = LGBMRegressor(
    n_estimators=500,
    learning_rate=0.05,
    max_depth=8,
    random_state=42
)

model.fit(X_train, y_train)


# -------------------------------
# 12. Evaluate Model
# -------------------------------

print("Evaluating model...")

predictions = model.predict(X_test)

mae = mean_absolute_error(y_test, predictions)
rmse = np.sqrt(mean_squared_error(y_test, predictions))

mape = np.mean(
    np.abs((y_test - predictions) / np.where(y_test == 0, 1, y_test))
) * 100

print("\nModel Evaluation Results:")
print(f"MAE  : {mae:.2f}")
print(f"RMSE : {rmse:.2f}")
print(f"MAPE : {mape:.2f}%")


# -------------------------------
# 13. Save Model and Encoders
# -------------------------------

print("Saving model...")

joblib.dump(model, f"{MODEL_PATH}/demand_forecasting_model.pkl")
joblib.dump(encoders, f"{MODEL_PATH}/label_encoders.pkl")
joblib.dump(features, f"{MODEL_PATH}/features.pkl")

print("\nModel training completed successfully!")
print("Saved files:")
print("models/demand_forecasting_model.pkl")
print("models/label_encoders.pkl")
print("models/features.pkl")